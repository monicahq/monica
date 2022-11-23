<?php

use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // users
    Route::middleware('abilities:read')->group(function () {
        Route::get('user', [UserController::class, 'user']);
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    });
});
