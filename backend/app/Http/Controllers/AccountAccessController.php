<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountAccessRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;

class AccountAccessController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateAccountAccessRequest $request, User $user): AuthenticatedUserResource
    {
        $attributes = $request->validated();

        if (array_key_exists('role', $attributes)) {
            $user->role = $attributes['role'];
        }

        if (array_key_exists('status', $attributes)) {
            $user->status = $attributes['status'];
        }

        $user->save();

        return new AuthenticatedUserResource($user);
    }
}
