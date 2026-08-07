<?php

declare(strict_types=1);

use App\Http\Controllers\App\Auth\EmailVerificationController;
use App\Http\Controllers\App\Auth\LocaleController;
use App\Http\Controllers\App\Auth\LoginController;
use App\Http\Controllers\App\Auth\MagicLinkController;
use App\Http\Controllers\App\Auth\NewPasswordController;
use App\Http\Controllers\App\Auth\PasswordResetLinkController;
use App\Http\Controllers\App\Auth\RegistrationController;
use App\Http\Controllers\App\Auth\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

// Creating an account.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('register', [RegistrationController::class, 'new'])->name('auth.register.new');
    Route::post('register', [RegistrationController::class, 'create'])->name('auth.register.create');
});

// Signing in with an email address and a password.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('login', [LoginController::class, 'new'])->name('auth.login.new');
    Route::post('login', [LoginController::class, 'create'])->name('auth.login.create');
});

// Signing in without a password, through a link sent by email.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('send-magic-link', [MagicLinkController::class, 'new'])->name('auth.magicLink.new');

    Route::post('send-magic-link', [MagicLinkController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.magicLink.create');

    Route::get('magic-link/{token}', [MagicLinkController::class, 'show'])
        ->middleware('throttle:6,1')
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('auth.magicLink.show');
});

// Asking for a new password, and choosing it.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('forgot-password', [PasswordResetLinkController::class, 'new'])->name('auth.password.new');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.password.create');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'edit'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('auth.password.edit');

    Route::post('reset-password', [NewPasswordController::class, 'update'])->name('auth.password.update');
});

// Answering the two factor challenge, once a password was accepted.
Route::middleware(['guest', 'set.locale'])->group(function (): void {
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'new'])->name('auth.twoFactor.new');

    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.twoFactor.create');
});

// Confirming the email address of a brand new account.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('auth.verification.notice');

    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->where('id', '[0-9]+')
        ->where('hash', '[a-f0-9]{40}')
        ->name('auth.verification.verify');

    Route::post('resend-verification-email', [EmailVerificationController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.verification.send');
});

// Signing out.
Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.login.destroy');

// Choosing the language of the interface, signed in or not.
Route::put('locale', [LocaleController::class, 'update'])
    ->middleware('set.locale')
    ->name('auth.locale.update');
