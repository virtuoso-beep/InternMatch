<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Opportunity;
use App\Services\Audit;
use App\Services\HostAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Coordinator, Role::Supervisor], true), 403);
        $query = Opportunity::whereIn('host_establishment_id', HostAccess::query($request->user())->select('id'));
        if ($request->user()->role === Role::Coordinator) {
            $query->whereHas('programs', fn ($program) => $program->whereIn('programs.id', $request->user()->programs()->select('programs.id')));
        }

        return response()->json($query->with(['programs', 'competencies', 'hostEstablishment'])->orderByDesc('id')->paginate(50));
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, int $opportunity)
    {
        $record = Opportunity::whereIn('host_establishment_id', HostAccess::query($request->user())->select('id'))->findOrFail($opportunity);
        HostAccess::authorizePrograms($request->user(), $record->programs()->pluck('programs.id')->all());

        return $this->save($request, $record);
    }

    private function save(Request $request, ?Opportunity $opportunity = null)
    {
        $data = $request->validate([
            'host_establishment_id' => ['required', 'integer'], 'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'title' => ['required', 'string', 'max:255'], 'description' => ['required', 'string', 'max:10000'],
            'tasks' => ['required', 'string', 'max:10000'], 'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
            'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'programs' => ['required', 'array', 'min:1', 'max:100'],
            'programs.*.program_id' => ['required', 'integer', 'distinct', 'exists:programs,id'],
            'programs.*.capacity' => ['required', 'integer', 'min:0', 'max:100000'],
            'competency_ids' => ['present', 'array', 'max:100'], 'competency_ids.*' => ['required', 'integer', 'distinct', 'exists:competencies,id'],
            'capacity' => ['prohibited'],
        ]);
        $host = HostAccess::query($request->user())->findOrFail($data['host_establishment_id']);
        HostAccess::authorizePrograms($request->user(), array_column($data['programs'], 'program_id'));
        $saved = DB::transaction(function () use ($request, $data, $opportunity, $host) {
            HostAccess::query($request->user())->whereKey($host->id)->lockForUpdate()->firstOrFail();
            if ($opportunity && ($opportunity->host_establishment_id !== $host->id || $opportunity->academic_term_id !== (int) $data['academic_term_id'])) {
                throw ValidationException::withMessages(['host_establishment_id' => 'The host and term of an existing opportunity cannot be changed.']);
            }
            $pivot = [];
            foreach ($data['programs'] as $program) {
                $hostCapacity = DB::table('host_program_capacity')->where('host_establishment_id', $host->id)
                    ->where('academic_term_id', $data['academic_term_id'])->where('program_id', $program['program_id'])->value('capacity');
                if ($hostCapacity === null || $program['capacity'] > $hostCapacity) {
                    throw ValidationException::withMessages(['programs' => 'Confirm host capacity for each program and term before assigning opportunity slots.']);
                }
                $pivot[$program['program_id']] = ['capacity' => $program['capacity']];
            }
            if ($opportunity) {
                foreach ($opportunity->placements()->whereIn('status', ['pending', 'approved', 'active', 'completed'])->get()->groupBy(fn ($placement) => $placement->studentEnrollment->programTerm->program_id) as $programId => $placements) {
                    if (($pivot[$programId]['capacity'] ?? 0) < $placements->count()) {
                        throw ValidationException::withMessages(['programs' => 'Capacity and program eligibility must preserve recorded placements.']);
                    }
                }
            }
            $attributes = array_diff_key($data, ['programs' => true, 'competency_ids' => true]);
            // Compatibility total only; eligibility and allocation always use the per-program rows.
            $attributes['capacity'] = array_sum(array_column($data['programs'], 'capacity'));
            $record = $opportunity ?? new Opportunity;
            $record->fill($attributes)->save();
            $record->programs()->sync($pivot);
            $record->competencies()->sync($data['competency_ids']);
            Audit::record($request->user(), $opportunity ? 'opportunity.updated' : 'opportunity.created', $record, ['programs' => $data['programs']]);

            return $record;
        });

        return response()->json(['data' => $saved->load(['programs', 'competencies'])], $opportunity ? 200 : 201);
    }
}
