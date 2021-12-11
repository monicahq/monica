<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vault\VaultController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\Users\UserController;
use App\Http\Controllers\Auth\AcceptInvitationController;

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

        // users
        Route::get('users', [UserController::class, 'index'])->name('settings.user.index');
        Route::get('users/create', [UserController::class, 'create'])->name('settings.user.create');
        Route::post('users', [UserController::class, 'store'])->name('settings.user.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('settings.user.show');
    });

    Route::get('contacts', 'ContactController@index');

    Route::resource('settings/information', 'Settings\\InformationController');

    // contacts
    Route::get('vaults/{vault}/contacts/{contact}', 'HomeController@index')->name('contact.show');
});
