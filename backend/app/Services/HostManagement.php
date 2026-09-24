<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\HostEstablishment;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HostManagement
{
    public static function create(User $actor, array $input): HostEstablishment
    {
        abort_unless($actor->status === AccountStatus::Active && in_array($actor->role, [Role::Admin, Role::Coordinator], true), 403);
        $data = self::validateHost($input);
        $capacity = self::validateCapacity($input);
        HostAccess::authorizePrograms($actor, array_column($capacity, 'program_id'));

        return DB::transaction(function () use ($actor, $data, $capacity) {
            $host = HostEstablishment::create($data);
            self::saveCapacity($host, $capacity);
            Audit::record($actor, 'host.created', $host, ['fields' => array_keys($data)]);

            return $host;
        });
    }

    public static function update(User $actor, HostEstablishment $host, array $input): HostEstablishment
    {
        return DB::transaction(function () use ($actor, $host, $input) {
            $record = HostAccess::query($actor)->lockForUpdate()->findOrFail($host->id);
            $data = self::validateHost($input, $record);
            abort_if($actor->role === Role::Supervisor && array_key_exists('is_active', $data), 403);
            $record->update($data);
            Audit::record($actor, 'host.updated', $record, ['fields' => array_keys($data)]);

            return $record;
        });
    }

    public static function updateCapacity(User $actor, HostEstablishment $host, array $input): void
    {
        $capacity = self::validateCapacity($input);
        HostAccess::authorizePrograms($actor, array_column($capacity, 'program_id'));
        DB::transaction(function () use ($actor, $host, $capacity) {
            $record = HostAccess::query($actor)->lockForUpdate()->findOrFail($host->id);
            self::saveCapacity($record, $capacity);
            Audit::record($actor, 'host.capacity_updated', $record, ['capacities' => $capacity]);
        });
    }

    public static function capacities(User $actor, HostEstablishment $host): array
    {
        HostAccess::query($actor)->findOrFail($host->id);
        $query = DB::table('host_program_capacity')->where('host_establishment_id', $host->id);
        if ($actor->role === Role::Coordinator) {
            $query->whereIn('program_id', $actor->programs()->select('programs.id'));
        }

        return $query->get(['program_id', 'academic_term_id', 'capacity'])->map(fn ($row) => (array) $row)->all();
    }

    private static function validateHost(array $input, ?HostEstablishment $host = null): array
    {
        $required = $host ? 'sometimes' : 'required';

        return Validator::make($input, [
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
        ])->validate();
    }

    private static function validateCapacity(array $input): array
    {
        $data = Validator::make($input, [
            'capacities' => ['required', 'array', 'min:1', 'max:100'],
            'capacities.*.program_id' => ['required', 'integer', 'exists:programs,id'],
            'capacities.*.academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
            'capacities.*.capacity' => ['required', 'integer', 'min:0', 'max:100000'],
        ])->validate()['capacities'];
        $keys = array_map(fn ($row) => $row['program_id'].':'.$row['academic_term_id'], $data);
        if (count(array_unique($keys)) !== count($keys)) {
            throw ValidationException::withMessages(['capacities' => 'Each program and term may appear only once.']);
        }

        return $data;
    }

    private static function saveCapacity(HostEstablishment $host, array $capacities): void
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
