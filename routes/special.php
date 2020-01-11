<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Route;

Route::get('/settings/emailchange1', [EmailChangeController::class, 'showLoginFormSpecial']);

Route::middleware('throttle:30,1')->group(function () {
    Route::post('/settings/emailchange1', [EmailChangeController::class, 'login']);
});

Route::middleware(['auth', '2fa', 'throttle:5,1'])->group(function () {
    Route::get('/settings/emailchange2', [EmailChangeController::class, 'index']);
    Route::post('/settings/emailchange2', [EmailChangeController::class, 'save']);
});
