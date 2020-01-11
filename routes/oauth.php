<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;

Route::middleware('oauth')->group(function () {
    Route::get('/login', [Api\Auth\OAuthController::class, 'index']);
    Route::post('/login', [Api\Auth\OAuthController::class, 'login'])->name('oauth.login');

    Route::middleware(['auth', 'mfa'])->group(function () {
        Route::post('/verified', [Api\Auth\OAuthController::class, 'verify'])->name('oauth.verify');
        Route::get('/verified', [Api\Auth\OAuthController::class, 'verify']);
    });

    Route::middleware(['auth', '2fa'])->group(function () {
        Route::post('/validate2fa', [Auth\Validate2faController::class, 'index'])->name('oauth.validate2fa');
    });
});
