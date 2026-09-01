<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\EmailSentController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\VaultController;
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

    Route::middleware(['auth:sanctum', 'throttle:60,1', 'set.locale'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');

        // The keys the caller has minted, including the one they are calling with.
        Route::get('api-keys', [ApiKeyController::class, 'index'])->name('apiKeys');
        Route::get('api-keys/{apiKey}', [ApiKeyController::class, 'show'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.show');
        Route::post('api-keys', [ApiKeyController::class, 'create'])->name('apiKeys.create');
        Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.destroy');

        // What has been done in the account, which the application writes and
        // nobody edits, so there is nothing here but reading.
        Route::get('logs', [LogController::class, 'index'])->name('logs');
        Route::get('logs/{log}', [LogController::class, 'show'])->where('log', '[1-9][0-9]*')->name('logs.show');

        // Every email that left on behalf of the account, and whether it
        // arrived. Written as mail leaves, so there is nothing here but reading.
        Route::get('emails-sent', [EmailSentController::class, 'index'])->name('emailsSent');
        Route::get('emails-sent/{emailSent}', [EmailSentController::class, 'show'])->where('emailSent', '[1-9][0-9]*')->name('emailsSent.show');

        // The compartments the account holds. Everything else lives in one, so
        // an account with no vault can hold nothing, which is why this is where
        // a caller setting an account up from a script starts.
        //
        // A vault is addressed by its uuid and by nothing else: the primary key
        // would say how many an instance holds, and the constraint means an
        // address of the wrong shape is refused before a query runs.
        Route::get('vaults', [VaultController::class, 'index'])->name('vaults');
        Route::get('vaults/{vault}', [VaultController::class, 'show'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.show');
        Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
        Route::put('vaults/{vault}', [VaultController::class, 'update'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.update');
        Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.destroy');
    });
});
