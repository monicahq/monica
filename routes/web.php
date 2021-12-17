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
use App\Http\Controllers\Settings\Personalize\Labels\PersonalizeLabelController;
use App\Http\Controllers\Settings\Personalize\Genders\PersonalizeGenderController;
use App\Http\Controllers\Settings\Personalize\Pronouns\PersonalizePronounController;
use App\Http\Controllers\Settings\Personalize\AddressTypes\PersonalizeAddressTypeController;
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

                // labels
                Route::get('labels', [PersonalizeLabelController::class, 'index'])->name('settings.personalize.label.index');
                Route::post('labels', [PersonalizeLabelController::class, 'store'])->name('settings.personalize.label.store');
                Route::put('labels/{label}', [PersonalizeLabelController::class, 'update'])->name('settings.personalize.label.update');
                Route::delete('labels/{label}', [PersonalizeLabelController::class, 'destroy'])->name('settings.personalize.label.destroy');

                // genders
                Route::get('genders', [PersonalizeGenderController::class, 'index'])->name('settings.personalize.gender.index');
                Route::post('genders', [PersonalizeGenderController::class, 'store'])->name('settings.personalize.gender.store');
                Route::put('genders/{gender}', [PersonalizeGenderController::class, 'update'])->name('settings.personalize.gender.update');
                Route::delete('genders/{gender}', [PersonalizeGenderController::class, 'destroy'])->name('settings.personalize.gender.destroy');

                // pronouns
                Route::get('pronouns', [PersonalizePronounController::class, 'index'])->name('settings.personalize.pronoun.index');
                Route::post('pronouns', [PersonalizePronounController::class, 'store'])->name('settings.personalize.pronoun.store');
                Route::put('pronouns/{pronoun}', [PersonalizePronounController::class, 'update'])->name('settings.personalize.pronoun.update');
                Route::delete('pronouns/{pronoun}', [PersonalizePronounController::class, 'destroy'])->name('settings.personalize.pronoun.destroy');

                // address types
                Route::get('addressTypes', [PersonalizeAddressTypeController::class, 'index'])->name('settings.personalize.address_type.index');
                Route::post('addressTypes', [PersonalizeAddressTypeController::class, 'store'])->name('settings.personalize.address_type.store');
                Route::put('addressTypes/{addressType}', [PersonalizeAddressTypeController::class, 'update'])->name('settings.personalize.address_type.update');
                Route::delete('addressTypes/{addressType}', [PersonalizeAddressTypeController::class, 'destroy'])->name('settings.personalize.address_type.destroy');
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
