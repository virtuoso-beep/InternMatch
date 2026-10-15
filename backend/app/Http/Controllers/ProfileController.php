<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function photo(Request $request)
    {
        $profile = $request->user()->profile;
        abort_unless($profile?->avatar_path && Storage::disk('local')->exists($profile->avatar_path), 404);
        return Storage::disk('local')->response($profile->avatar_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function uploadPhoto(Request $request): ProfileResource
    {
        abort_unless($request->user()->hasPermission(Permission::ManageOwnProfile), 403);
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:max_width=4096,max_height=4096']]);
        $path = $request->file('photo')->store('avatars', 'local');
        abort_unless(is_string($path), 503, 'Photo could not be stored.');
        try {
            DB::transaction(function () use ($request, $path) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $user->profile()->updateOrCreate([], ['avatar_disk' => 'local', 'avatar_path' => $path]);
                Audit::record($user, 'profile.photo_updated', $user, []);
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($path);
            throw $error;
        }
        return $this->resource($request->user());
    }

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
