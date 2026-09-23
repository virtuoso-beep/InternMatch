<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Competency;
use App\Models\Program;
use App\Services\Audit;
use App\Services\EnrollmentProvisioning;
use App\Services\HostAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicController extends Controller
{
    public function reference(Request $request)
    {
        $programs = Program::where('is_active', true);
        if (in_array($request->user()->role, [Role::Coordinator, Role::Dean], true)) {
            $programs->whereIn('id', $request->user()->programs()->select('programs.id'));
        }

        return response()->json([
            'hosts' => HostAccess::query($request->user())->orderBy('name')->get(['id', 'name']),
            'programs' => $programs->orderBy('code')->get(['id', 'code', 'name', 'required_ojt_hours']),
            'terms' => AcademicTerm::where('is_active', true)->orderByDesc('starts_on')->get(),
            'competencies' => Competency::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeTerm(Request $request)
    {
        abort_unless($request->user()->hasPermission(Permission::ManageAcademicRecords), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:academic_terms'],
            'academic_year' => ['required', 'string', 'max:30'], 'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);
        $term = DB::transaction(function () use ($request, $data) {
            $term = AcademicTerm::create($data);
            Audit::record($request->user(), 'academic_term.created', $term, $data);

            return $term;
        });

        return response()->json(['data' => $term], 201);
    }

    public function enroll(Request $request)
    {
        $record = EnrollmentProvisioning::create($request->user(), $request->all());

        return response()->json(['data' => $record], 201);
    }
}
