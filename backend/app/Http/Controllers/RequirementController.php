<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Document;
use App\Models\ProgramTerm;
use App\Models\RequirementSubmission;
use App\Models\RequirementType;
use App\Models\StudentEnrollment;
use App\Services\Audit;
use App\Services\RequirementReviewService;
use App\Services\StudentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RequirementController extends Controller
{
    public function configuration(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission(Permission::ManageAcademicRecords) || $user->hasPermission(Permission::ReviewRequirements), 403);
        $terms = ProgramTerm::with(['program:id,code', 'academicTerm:id,code,name', 'requirements.requirementType']);
        if (! $user->hasPermission(Permission::ManageAcademicRecords)) {
            $terms->whereIn('program_id', $user->programs()->select('programs.id'));
        }

        return response()->json(['program_terms' => $terms->get(), 'types' => RequirementType::where('is_active', true)->orderBy('name')->get(['id', 'name'])]);
    }

    public function index(Request $request, int $enrollment)
    {
        abort_unless(in_array($request->user()->role, [Role::Student, Role::Coordinator, Role::Admin], true), 403);
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        $requirements = $student->programTerm->requirements()->with('requirementType')->get();
        $submissions = $student->requirementSubmissions()->with(['document', 'reviews'])->orderByDesc('revision')->get();

        return response()->json(['requirements' => $requirements, 'submissions' => $submissions], headers: ['Cache-Control' => 'no-store']);
    }

    public function configure(Request $request, int $programTerm)
    {
        $term = ProgramTerm::findOrFail($programTerm);
        $user = $request->user();
        abort_unless($user->hasPermission(Permission::ManageAcademicRecords)
            || ($user->hasPermission(Permission::ReviewRequirements) && $user->isAssignedToProgramTerm($term->id)), 403);
        $data = $request->validate([
            'requirement_type_id' => ['required', 'integer', 'exists:requirement_types,id'],
            'is_required' => ['required', 'boolean'], 'required_before_deployment' => ['required', 'boolean'], 'due_at' => ['nullable', 'date'],
        ]);
        $record = DB::transaction(function () use ($request, $term, $data) {
            $record = $term->requirements()->updateOrCreate(['requirement_type_id' => $data['requirement_type_id']], $data);
            Audit::record($request->user(), 'requirement.configured', $record, $data, $term->program_id);

            return $record;
        });

        return response()->json(['data' => $record]);
    }

    public function submit(Request $request, int $enrollment)
    {
        $student = StudentAccess::enrollments($request->user())->findOrFail($enrollment);
        Gate::authorize('submitRequirements', $student);
        $data = $request->validate(['requirement_id' => ['required', 'integer'], 'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,docx', 'max:10240']]);
        $requirement = $student->programTerm->requirements()->findOrFail($data['requirement_id']);
        $file = $request->file('file');
        $path = $file->store('requirements', 'local');
        abort_unless(is_string($path), 503, 'The document could not be stored. Please try again.');
        try {
            $submission = DB::transaction(function () use ($request, $student, $requirement, $file, $path) {
                StudentEnrollment::whereKey($student->id)->lockForUpdate()->firstOrFail();
                $revision = $student->requirementSubmissions()->where('program_term_requirement_id', $requirement->id)->max('revision') + 1;
                $document = Document::create(['uploaded_by' => $request->user()->id, 'disk' => 'local', 'path' => $path,
                    'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath())]);
                $submission = $student->requirementSubmissions()->create([
                    'program_term_id' => $student->program_term_id, 'program_term_requirement_id' => $requirement->id,
                    'document_id' => $document->id, 'revision' => $revision, 'status' => 'submitted', 'submitted_at' => now(),
                ]);
                Audit::record($request->user(), 'requirement.submitted', $submission, ['revision' => $revision], $student->programTerm->program_id);

                return $submission;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['data' => $submission->load('document')], 201);
    }

    public function download(Request $request, RequirementSubmission $submission)
    {
        Gate::authorize('view', $submission);
        $document = $submission->document;
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function review(Request $request, RequirementSubmission $submission)
    {
        return response()->json(['data' => RequirementReviewService::review($request->user(), $submission, $request->all())]);
    }
}
