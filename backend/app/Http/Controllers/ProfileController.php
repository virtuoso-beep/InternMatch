<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return $this->resource($request->user());
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $data = $request->validated();
        $user = DB::transaction(function () use ($request, $data): User {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if (array_key_exists('name', $data)) {
                $user->update(['name' => $data['name']]);
            }
            $profile = array_diff_key($data, ['name' => true]);
            if ($profile !== []) {
                $user->profile()->updateOrCreate([], $profile);
            }
            Audit::record($user, 'profile.updated', $user, ['fields' => array_keys($data)]);

            return $user;
        });
        Log::info('profile.updated', ['user_id' => $user->id, 'fields' => array_keys($data)]);

        return $this->resource($user);
    }

    private function resource(User $user): ProfileResource
    {
        return new ProfileResource($user->load(['profile', 'student.enrollments.programTerm.program', 'student.enrollments.programTerm.academicTerm']));
    }
}
