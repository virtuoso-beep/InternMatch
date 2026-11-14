<?php

namespace App\Http\Resources;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class AuthenticatedUserResource extends JsonResource
{
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->header('Cache-Control', 'no-store');
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $programs = match ($this->role?->value) {
            'student' => \App\Models\Program::whereHas('programTerms.enrollments.student', fn ($query) => $query->where('user_id', $this->id))->orderBy('code')->pluck('code')->all(),
            'coordinator', 'dean' => $this->programs()->orderBy('code')->pluck('code')->all(),
            default => [],
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'must_change_password' => (bool) $this->must_change_password,
            'student_number' => $this->role?->value === 'student' ? $this->student?->student_number : null,
            'role' => $this->role?->value,
            'status' => $this->status->value,
            'program_codes' => $programs,
            'program_label' => match ($this->role?->value) {
                'admin' => 'All programs',
                'supervisor' => 'Assigned interns',
                default => $programs ? implode(', ', $programs) : 'No program assigned',
            },
            'permissions' => array_values(array_map(
                fn (Permission $permission): string => $permission->value,
                array_filter(Permission::cases(), fn (Permission $permission): bool => $this->hasPermission($permission)),
            )),
        ];
    }
}
