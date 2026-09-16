<?php

namespace App\Http\Resources;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class AuthenticatedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value,
            'status' => $this->status->value,
            'permissions' => array_values(array_map(
                fn (Permission $permission): string => $permission->value,
                array_filter(Permission::cases(), fn (Permission $permission): bool => $this->hasPermission($permission)),
            )),
        ];
    }
}
