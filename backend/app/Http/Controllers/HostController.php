<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\HostEstablishment;
use App\Models\Placement;
use App\Services\Audit;
use App\Services\HostAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HostController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Coordinator, Role::Supervisor], true), 403);

        return response()->json(HostAccess::query($request->user())->orderBy('name')->paginate(50), headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Coordinator], true), 403);
        $data = $this->validateHost($request);
        $capacity = $this->validateCapacity($request);
        HostAccess::authorizePrograms($request->user(), array_column($capacity, 'program_id'));
        $host = DB::transaction(function () use ($request, $data, $capacity) {
            $host = HostEstablishment::create($data);
            $this->saveCapacity($host, $capacity);
            Audit::record($request->user(), 'host.created', $host, ['fields' => array_keys($data)]);

            return $host;
        });

        return response()->json(['data' => $host], 201);
    }

    public function update(Request $request, int $host)
    {
        $record = HostAccess::query($request->user())->findOrFail($host);
        $data = $this->validateHost($request, $record);
        abort_if($request->user()->role === Role::Supervisor && array_key_exists('is_active', $data), 403);
        DB::transaction(function () use ($request, $record, $data) {
            $record->update($data);
            Audit::record($request->user(), 'host.updated', $record, ['fields' => array_keys($data)]);
        });

        return response()->json(['data' => $record->fresh()]);
    }

    public function capacities(Request $request, int $host)
    {
        $record = HostAccess::query($request->user())->findOrFail($host);
        $query = DB::table('host_program_capacity')->where('host_establishment_id', $record->id);
        if ($request->user()->role === Role::Coordinator) {
            $query->whereIn('program_id', $request->user()->programs()->select('programs.id'));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function updateCapacity(Request $request, int $host)
    {
        $record = HostAccess::query($request->user())->findOrFail($host);
        $capacity = $this->validateCapacity($request);
        HostAccess::authorizePrograms($request->user(), array_column($capacity, 'program_id'));
        DB::transaction(function () use ($request, $record, $capacity) {
            HostEstablishment::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->saveCapacity($record, $capacity);
            Audit::record($request->user(), 'host.capacity_updated', $record, ['capacities' => $capacity]);
        });

        return $this->capacities($request, $host);
    }

    private function validateHost(Request $request, ?HostEstablishment $host = null): array
    {
        $required = $host ? 'sometimes' : 'required';

        return $request->validate([
            'code' => [$required, 'string', 'max:50', Rule::unique('host_establishments')->ignore($host?->id)],
            'name' => [$required, 'string', 'max:255'], 'address' => [$required, 'string', 'max:255'],
            'city' => [$required, 'string', 'max:255'], 'industry' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'contact_number' => ['sometimes', 'nullable', 'string', 'max:40'],
            'latitude' => ['present_with:longitude', 'nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['present_with:latitude', 'nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function validateCapacity(Request $request): array
    {
        $data = $request->validate([
            'capacities' => ['required', 'array', 'min:1', 'max:100'],
            'capacities.*.program_id' => ['required', 'integer', 'exists:programs,id'],
            'capacities.*.academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'capacities.*.capacity' => ['required', 'integer', 'min:0', 'max:100000'],
        ])['capacities'];
        $keys = array_map(fn ($row) => $row['program_id'].':'.$row['academic_term_id'], $data);
        if (count(array_unique($keys)) !== count($keys)) {
            throw ValidationException::withMessages(['capacities' => 'Each program and term may appear only once.']);
        }

        return $data;
    }

    private function saveCapacity(HostEstablishment $host, array $capacities): void
    {
        foreach ($capacities as $row) {
            $occupied = Placement::where('host_establishment_id', $host->id)->whereIn('status', ['pending', 'approved', 'active', 'completed'])
                ->whereHas('studentEnrollment.programTerm', fn ($query) => $query->where('program_id', $row['program_id'])->where('academic_term_id', $row['academic_term_id']))->count();
            if ($row['capacity'] < $occupied) {
                throw ValidationException::withMessages(['capacities' => 'Capacity cannot be reduced below recorded placements.']);
            }
            DB::table('host_program_capacity')->updateOrInsert([
                'host_establishment_id' => $host->id, 'program_id' => $row['program_id'], 'academic_term_id' => $row['academic_term_id'],
            ], ['capacity' => $row['capacity'], 'updated_at' => now()]);
        }
    }
}
