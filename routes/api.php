<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

/*
 * Version one of the API. The prefix is real rather than decorative: a caller
 * who writes /api/v1 today keeps working the day a second version arrives
 * beside it, which is what the rest of the product promises about anything
 * somebody has already built on.
 *
 * The two routes that take a password are held to a tighter limit than the
 * rest, so that neither of them can be used to guess one.
 */
Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('status', [StatusController::class, 'show'])->middleware('throttle:60,1')->name('status');

    Route::post('register', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('register');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');
    });
});
