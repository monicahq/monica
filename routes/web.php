<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vault\VaultController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\Users\UserController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Settings\Personalize\PersonalizeController;
use App\Http\Controllers\Settings\Preferences\PreferencesController;
use App\Http\Controllers\Settings\CancelAccount\CancelAccountController;
use App\Http\Controllers\Settings\Personalize\Relationships\PersonalizeRelationshipController;
use App\Http\Controllers\Settings\Personalize\Relationships\PersonalizeRelationshipTypeController;

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
            // preferences
            Route::prefix('preferences')->group(function () {
                Route::get('', [PreferencesController::class, 'index'])->name('settings.preferences.index');
                Route::post('', [PreferencesController::class, 'store'])->name('settings.preferences.store');
            });

            // users
            Route::prefix('users')->group(function () {
                Route::get('', [UserController::class, 'index'])->name('settings.user.index');
                Route::get('create', [UserController::class, 'create'])->name('settings.user.create');
                Route::post('', [UserController::class, 'store'])->name('settings.user.store');
                Route::get('{user}', [UserController::class, 'show'])->name('settings.user.show');
            });

            // personalize
            Route::prefix('personalize')->group(function () {
                Route::get('', [PersonalizeController::class, 'index'])->name('settings.personalize.index');

                // relationship group types
                Route::get('relationships', [PersonalizeRelationshipController::class, 'index'])->name('settings.personalize.relationship.index');
                Route::post('relationships', [PersonalizeRelationshipController::class, 'store'])->name('settings.personalize.relationship.grouptype.store');
                Route::put('relationships/{groupType}', [PersonalizeRelationshipController::class, 'update'])->name('settings.personalize.relationship.grouptype.update');
                Route::delete('relationships/{groupType}', [PersonalizeRelationshipController::class, 'destroy'])->name('settings.personalize.relationship.grouptype.destroy');

                // relationship group types
                Route::post('relationships/{groupType}/types', [PersonalizeRelationshipTypeController::class, 'store'])->name('settings.personalize.relationship.type.store');
                Route::put('relationships/{groupType}/types/{type}', [PersonalizeRelationshipTypeController::class, 'update'])->name('settings.personalize.relationship.type.update');
                Route::delete('relationships/{groupType}/types/{type}', [PersonalizeRelationshipTypeController::class, 'destroy'])->name('settings.personalize.relationship.type.destroy');
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
