<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountAccessRequest;
use App\Http\Resources\AuthenticatedUserResource;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Support\Facades\DB;

class AccountAccessController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateAccountAccessRequest $request, User $user): AuthenticatedUserResource
    {
        $attributes = $request->validated();

        $user = DB::transaction(function () use ($request, $user, $attributes) {
            $record = User::query()->lockForUpdate()->findOrFail($user->id);
            $before = ['role' => $record->role?->value, 'status' => $record->status->value];
            if (array_key_exists('role', $attributes)) {
                $record->role = $attributes['role'];
            }
            if (array_key_exists('status', $attributes)) {
                $record->status = $attributes['status'];
            }
            $record->save();
            Audit::record($request->user(), 'account.access_updated', $record, ['before' => $before, 'after' => $attributes]);

            return $record;
        });

        return new AuthenticatedUserResource($user);
    }
}
