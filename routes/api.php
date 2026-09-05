<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\ContactAvatarController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactFavouriteController;
use App\Http\Controllers\Api\ContactNameController;
use App\Http\Controllers\Api\ContactTemplateVersionController;
use App\Http\Controllers\Api\EmailSentController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeTimezoneController;
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

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');
        Route::put('me/timezone', [MeTimezoneController::class, 'update'])->name('me.timezone.update');

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

        // The people an account knows, under the vault each of them lives in.
        // The vault is in the address rather than implied, because a contact is
        // reached through the compartment holding them and through nothing else,
        // which is what every contact screen does.
        //
        // How the list is narrowed, searched and ordered is a query string here
        // rather than a path segment. The screen puts the grouping in the path so
        // a chip is somewhere to link to; this cannot, because `contacts/{filter}`
        // and `contacts/{contact}` are the same shape.
        Route::get('vaults/{vault}/contacts', [ContactController::class, 'index'])->where('vault', '[0-9a-fA-F-]{36}')->name('contacts');
        Route::get('vaults/{vault}/contacts/{contact}', [ContactController::class, 'show'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.show');
        Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->where('vault', '[0-9a-fA-F-]{36}')->name('contacts.create');
        Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.destroy');

        // The name is its own address because it is the one thing about a contact
        // the product imposes rather than the template, and because writing it
        // replaces every part at once: sending it alongside anything else would
        // hide that.
        Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.name.update');

        // The three things the product asks about somebody whatever structure
        // they are on, each its own address because none of them is a field.
        //
        // The picture is posted rather than put, because PHP fills in an uploaded
        // file on a POST and on nothing else, which is why the browser posts it too.
        Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.favourite.update');
        Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.avatar.update');
        Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.avatar.destroy');
        Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactTemplateVersionController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.templateVersion.update');
    });
});
