<?php

namespace App\Providers;

use App\Enums\Permission;
use App\Models\Placement;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\ChatAccess;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StudentEnrollment::created(fn ($enrollment) => ChatAccess::ensure($enrollment));
        Placement::saved(fn ($placement) => ChatAccess::ensure($placement->studentEnrollment, $placement));
        // This first-party SPA uses sessions only; no personal access tokens are issued.
        Sanctum::getAccessTokenFromRequestUsing(fn (): null => null);

        foreach (Permission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasPermission($permission));
        }

        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(30)->by('ip:'.$request->ip()),
        ]);
    }
}
