<?php

declare(strict_types=1);

use App\Enums\SsoProviderEnum;
use App\Http\Controllers\App\Auth\ConfirmPasswordController;
use App\Http\Controllers\App\Auth\EmailVerificationController;
use App\Http\Controllers\App\Auth\LocaleController;
use App\Http\Controllers\App\Auth\LoginController;
use App\Http\Controllers\App\Auth\MagicLinkController;
use App\Http\Controllers\App\Auth\NewPasswordController;
use App\Http\Controllers\App\Auth\PasswordConfirmationLinkController;
use App\Http\Controllers\App\Auth\PasswordConfirmationStatusController;
use App\Http\Controllers\App\Auth\PasswordResetLinkController;
use App\Http\Controllers\App\Auth\RegistrationController;
use App\Http\Controllers\App\Auth\RegistrationPasskeyController;
use App\Http\Controllers\App\Auth\ScreenshotLinkController;
use App\Http\Controllers\App\Auth\SsoController;
use App\Http\Controllers\App\Auth\TwoFactorChallengeController;
use App\Http\Controllers\App\Auth\TwoFactorKeyOptionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest'])->group(function (): void {
    /*
     * Creating an account. The whole flow answers 404 while `monica.registration_enabled` is off.
     * The second passkey step is not here: it runs for an account that already exists.
     */
    Route::middleware(['registration'])->group(function (): void {
        Route::get('register', [RegistrationController::class, 'new'])->name('auth.register.new');
        Route::post('register', [RegistrationController::class, 'create'])->name('auth.register.create');

        Route::post('register/passkey', [RegistrationPasskeyController::class, 'create'])->name('auth.registerPasskey.create');
    });

    Route::put('locale', [LocaleController::class, 'update'])
        ->name('auth.locale.update');

    Route::get('login', [LoginController::class, 'new'])->name('auth.login.new');
    Route::post('login', [LoginController::class, 'create'])->name('auth.login.create');

    /*
     * Signing in with a link that arrives by email rather than with a password. The whole flow
     * answers 404 while `monica.magic_link_enabled` is off, the request screen and the link
     * included, so an instance that switches it off does not carry it at all.
     */
    Route::middleware(['magic.link'])->group(function (): void {
        Route::get('send-magic-link', [MagicLinkController::class, 'new'])->name('auth.magicLink.new');
        Route::post('send-magic-link', [MagicLinkController::class, 'create'])
            ->middleware('throttle:6,1')
            ->name('auth.magicLink.create');
        Route::get('magic-link/{token}', [MagicLinkController::class, 'show'])
            ->middleware(['signed', 'throttle:6,1'])
            ->where('token', '[A-Za-z0-9]{64}')
            ->name('auth.magicLink.show');
    });

    /*
     * Signing in through an outside provider. A provider the instance has not both
     * installed and given credentials for does not exist here: the middleware
     * answers 404 and the sign in screen offers nothing.
     */
    Route::get('sso/{provider}/redirect', [SsoController::class, 'redirect'])
        ->middleware(['sso', 'throttle:6,1'])
        ->whereIn('provider', SsoProviderEnum::values())
        ->name('auth.sso.redirect');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'new'])->name('auth.password.new');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.password.create');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'edit'])
        ->middleware(['signed', 'throttle:6,1'])
        ->where('token', '[A-Za-z0-9]+')
        ->name('auth.password.edit');
    Route::post('reset-password', [NewPasswordController::class, 'update'])->name('auth.password.update');

    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'new'])->name('auth.twoFactor.new');
    Route::get('two-factor-challenge/key-options', [TwoFactorKeyOptionsController::class, 'show'])
        ->middleware('throttle:12,1,two-factor-key-options')
        ->name('auth.twoFactorKeyOptions.show');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.twoFactor.create');
});

/*
 * Where a provider sends everybody back. It is registered once with the provider
 * and serves both flows, so it sits outside the guest group: signing in arrives
 * here, and so does a signed in person who has just connected one.
 */
Route::get('sso/{provider}/callback', [SsoController::class, 'callback'])
    ->middleware(['sso', 'throttle:6,1'])
    ->whereIn('provider', SsoProviderEnum::values())
    ->name('auth.sso.callback');

Route::middleware(['auth'])->group(function (): void {
    Route::get('register/passkey', [RegistrationPasskeyController::class, 'new'])->name('auth.registerPasskey.new');

    /*
     * The screen a sensitive action is sent to when the session holds no recent confirmation. It
     * takes the password, the passkey package's own endpoints take a passkey, and the link sent by
     * email works for every account, all of them writing the one session fact `password.confirm`
     * reads. The link confirms only a session signed in as the person it was sent to.
     */
    Route::get('confirm-password', [ConfirmPasswordController::class, 'new'])->name('auth.confirmPassword.new');
    Route::post('confirm-password', [ConfirmPasswordController::class, 'create'])
        ->middleware('throttle:confirm-password')
        ->name('auth.confirmPassword.create');
    Route::post('confirm-password/link', [PasswordConfirmationLinkController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.passwordConfirmationLink.create');
    Route::get('confirm-password/link/{id}', [PasswordConfirmationLinkController::class, 'show'])
        ->middleware(['signed', 'throttle:6,1'])
        ->where('id', '[0-9]+')
        ->name('auth.passwordConfirmationLink.show');
    Route::get('confirm-password/status', [PasswordConfirmationStatusController::class, 'show'])->name('auth.passwordConfirmationStatus.show');

    Route::get('verify-email', [EmailVerificationController::class, 'show'])->name('auth.verification.notice');
    Route::get('verify-email/{id}/{hash}', [EmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->where('id', '[0-9]+')
        ->where('hash', '[a-f0-9]{40}')
        ->name('auth.verification.verify');
    Route::post('resend-verification-email', [EmailVerificationController::class, 'create'])
        ->middleware('throttle:6,1')
        ->name('auth.verification.send');

    Route::post('logout', [LoginController::class, 'destroy'])
        ->name('auth.login.destroy');
});

/*
 * The link a screenshot tool follows to reach a screen behind the sign in. It is registered outside
 * production and nowhere else, and it still has to be switched on, answered on a development host
 * and signed before it leads anywhere. The controller says the rest.
 */
if (app()->environment('local', 'testing')) {
    Route::get('screenshot-link/{email}', [ScreenshotLinkController::class, 'show'])
        ->middleware(['signed', 'throttle:20,1'])
        ->where('email', '[^/]+')
        ->name('auth.screenshotLink.show');
}
