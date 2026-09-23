<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Document;
use App\Models\HostEstablishment;
use App\Models\Moa;
use App\Services\Audit;
use App\Services\HostAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class MoaController extends Controller
{
    private function query(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, [Role::Admin, Role::Coordinator, Role::Supervisor], true), 403);
        $query = Moa::whereIn('host_establishment_id', HostAccess::query($user)->select('id'));
        if ($user->role === Role::Coordinator) {
            $query->where(fn ($query) => $query->where('is_institution_wide', true)->orWhereHas('programs', fn ($programs) => $programs->whereIn('programs.id', $user->programs()->select('programs.id'))));
        }

        return $query;
    }

    private function authorizeEdit(Request $request, Moa $moa): void
    {
        abort_if($request->user()->role === Role::Supervisor && $moa->status->value !== 'draft', 403, 'Only draft records may be edited by a host supervisor.');
        if ($request->user()->role === Role::Coordinator) {
            abort_if($moa->is_institution_wide, 403, 'Institution-wide coverage is maintained by the central registry.');
            HostAccess::authorizePrograms($request->user(), $moa->programs()->pluck('programs.id')->all());
        }
    }

    public function index(Request $request)
    {
        return response()->json($this->query($request)->with(['hostEstablishment:id,name', 'programs:id,code', 'document'])->latest()->paginate(30), headers: ['Cache-Control' => 'no-store']);
    }

    public function save(Request $request, ?int $moa = null)
    {
        $existing = $moa === null ? null : $this->query($request)->findOrFail($moa);
        if ($existing) {
            $this->authorizeEdit($request, $existing);
        }
        $user = $request->user();
        abort_unless(in_array($user->role, [Role::Admin, Role::Coordinator, Role::Supervisor], true), 403);
        $data = $request->validate([
            'host_establishment_id' => ['required', 'integer'], 'reference_number' => ['required', 'string', 'max:80', Rule::unique('moas')->ignore($existing?->id)],
            'status' => ['required', Rule::in(['draft', 'pending_signature', 'active', 'expired', 'terminated'])],
            'effective_on' => ['required_if:status,active', 'nullable', 'date_format:Y-m-d'],
            'expires_on' => ['required_if:status,active', 'nullable', 'date_format:Y-m-d', 'after_or_equal:effective_on'],
            'is_institution_wide' => ['required', 'boolean'], 'program_ids' => ['present', 'array', 'max:100'],
            'program_ids.*' => ['required', 'integer', 'distinct', 'exists:programs,id'], 'notes' => ['nullable', 'string', 'max:10000'],
            'max_interns_per_term' => ['prohibited'], 'document_id' => ['prohibited'],
        ]);
        $host = HostAccess::query($user)->findOrFail($data['host_establishment_id']);
        abort_if($existing && $existing->host_establishment_id !== $host->id, 422, 'An agreement cannot be moved to another host.');
        HostAccess::authorizePrograms($user, $data['program_ids']);
        abort_if($user->role !== Role::Admin && $data['is_institution_wide'], 403);
        abort_if($user->role === Role::Supervisor && $data['status'] !== 'draft', 403, 'Host supervisors can register draft agreements for institutional review.');
        abort_if($data['is_institution_wide'] && $data['program_ids'] !== [], 422, 'Choose institution-wide coverage or specific programs, not both.');
        abort_if(! $data['is_institution_wide'] && $data['program_ids'] === [], 422, 'Specify the programs covered by the agreement.');
        $record = DB::transaction(function () use ($request, $host, $existing, $data) {
            HostEstablishment::whereKey($host->id)->lockForUpdate()->firstOrFail();
            $record = $existing ? Moa::whereKey($existing->id)->lockForUpdate()->firstOrFail() : new Moa;
            if ($record->exists) {
                $this->authorizeEdit($request, $record);
                if ($record->placements()->exists()) {
                    $oldPrograms = $record->programs()->pluck('programs.id')->sort()->values()->all();
                    $newPrograms = collect($data['program_ids'])->map(fn ($id) => (int) $id)->sort()->values()->all();
                    abort_if($oldPrograms !== $newPrograms || $record->is_institution_wide !== (bool) $data['is_institution_wide']
                        || $record->reference_number !== $data['reference_number']
                        || $record->effective_on?->toDateString() !== ($data['effective_on'] ?? null)
                        || $record->expires_on?->toDateString() !== ($data['expires_on'] ?? null), 409, 'An agreement used by placements retains its original coverage and dates. Register a new agreement for changes.');
                }
            }
            abort_if($data['status'] === 'active' && ! $record->document_id, 422, 'Attach the executed agreement document before recording active status.');
            $record->fill(collect($data)->except('program_ids')->all())->save();
            $record->programs()->sync($data['program_ids']);
            Audit::record($request->user(), 'moa.saved', $record, ['status' => $data['status'], 'program_ids' => $data['program_ids'], 'is_institution_wide' => $data['is_institution_wide']], count($data['program_ids']) === 1 ? $data['program_ids'][0] : null);

            return $record;
        });

        return response()->json(['data' => $record->load(['programs', 'document'])], $existing ? 200 : 201);
    }

    public function upload(Request $request, int $moa)
    {
        $record = $this->query($request)->findOrFail($moa);
        $this->authorizeEdit($request, $record);
        $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,docx', 'max:10240']]);
        $file = $request->file('file');
        $path = $file->store('agreements', 'local');
        abort_unless(is_string($path), 503, 'Unable to store the document.');
        try {
            DB::transaction(function () use ($request, $record, $file, $path) {
                HostEstablishment::whereKey($record->host_establishment_id)->lockForUpdate()->firstOrFail();
                $current = Moa::whereKey($record->id)->lockForUpdate()->firstOrFail();
                $this->authorizeEdit($request, $current);
                abort_if($current->status->value === 'active' || $current->placements()->exists(), 409, 'Create a new agreement record to replace an executed document used by placements.');
                $document = Document::create(['uploaded_by' => $request->user()->id, 'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath())]);
                $current->update(['document_id' => $document->id]);
                Audit::record($request->user(), 'moa.document_uploaded', $current, ['document_id' => $document->id]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['data' => $record->fresh()->load('document')]);
    }

    public function download(Request $request, int $moa)
    {
        $record = $this->query($request)->findOrFail($moa);
        $document = $record->document;
        abort_unless($document && Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
