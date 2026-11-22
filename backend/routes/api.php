<?php

use App\Http\Controllers\AccountAccessController;
use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login')->name('api.login');

Route::middleware(['auth:sanctum', 'account.enabled'])->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('api.logout');
    Route::get('/user', [SessionController::class, 'show'])->name('api.user');
    Route::patch('/users/{user}/access', AccountAccessController::class)
        ->middleware('can:manage_accounts')
        ->can('updateAccess', 'user')
        ->name('api.users.access.update');
});
