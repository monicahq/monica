<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vault\VaultController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\Users\UserController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Settings\CancelAccount\CancelAccountController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

require __DIR__.'/auth.php';

Route::get('invitation/{code}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
Route::post('invitation', [AcceptInvitationController::class, 'store'])->name('invitation.store');

Route::middleware(['auth', 'verified'])->group(function () {
    // vaults
    Route::prefix('vaults')->group(function () {
        Route::get('', [VaultController::class, 'index'])->name('vault.index');
        Route::get('create', [VaultController::class, 'create'])->name('vault.create');
        Route::post('', [VaultController::class, 'store'])->name('vault.store');

        Route::middleware(['vault'])->prefix('{vault}')->group(function () {
            Route::get('', [VaultController::class, 'show'])->name('vault.show');
        });
    });

    // settings
    Route::prefix('settings')->group(function () {
        Route::get('', [SettingsController::class, 'index'])->name('settings.index');

        // only for administrators
        Route::middleware(['administrator'])->group(function () {
            // users
            Route::prefix('users')->group(function () {
                Route::get('', [UserController::class, 'index'])->name('settings.user.index');
                Route::get('create', [UserController::class, 'create'])->name('settings.user.create');
                Route::post('', [UserController::class, 'store'])->name('settings.user.store');
                Route::get('{user}', [UserController::class, 'show'])->name('settings.user.show');
            });

            // cancel
            Route::get('cancel', [CancelAccountController::class, 'index'])->name('settings.cancel.index');
            Route::put('cancel', [CancelAccountController::class, 'destroy'])->name('settings.cancel.destroy');
        });
    });

    Route::get('contacts', 'ContactController@index');

    Route::resource('settings/information', 'Settings\\InformationController');

    // contacts
    Route::get('vaults/{vault}/contacts/{contact}', 'HomeController@index')->name('contact.show');
});
