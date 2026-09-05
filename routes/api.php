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
use App\Http\Controllers\Api\EntityTypeActivationController;
use App\Http\Controllers\Api\EntityTypeController;
use App\Http\Controllers\Api\EntityTypePositionController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeTimezoneController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\VaultController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('status', [StatusController::class, 'show'])->middleware('throttle:60,1')->name('status');

    Route::post('register', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('register');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');
        Route::put('me/timezone', [MeTimezoneController::class, 'update'])->name('me.timezone.update');

        Route::get('api-keys', [ApiKeyController::class, 'index'])->name('apiKeys');
        Route::get('api-keys/{apiKey}', [ApiKeyController::class, 'show'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.show');
        Route::post('api-keys', [ApiKeyController::class, 'create'])->name('apiKeys.create');
        Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.destroy');

        Route::get('logs', [LogController::class, 'index'])->name('logs');
        Route::get('logs/{log}', [LogController::class, 'show'])->where('log', '[1-9][0-9]*')->name('logs.show');

        Route::get('emails-sent', [EmailSentController::class, 'index'])->name('emailsSent');
        Route::get('emails-sent/{emailSent}', [EmailSentController::class, 'show'])->where('emailSent', '[1-9][0-9]*')->name('emailsSent.show');

        Route::get('vaults', [VaultController::class, 'index'])->name('vaults');
        Route::get('vaults/{vault}', [VaultController::class, 'show'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.show');
        Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
        Route::put('vaults/{vault}', [VaultController::class, 'update'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.update');
        Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.destroy');

        Route::get('entity-types', [EntityTypeController::class, 'index'])->name('entityTypes');
        Route::get('entity-types/{entityType}', [EntityTypeController::class, 'show'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.show');
        Route::post('entity-types', [EntityTypeController::class, 'create'])->name('entityTypes.create');
        Route::put('entity-types/{entityType}', [EntityTypeController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.update');
        Route::delete('entity-types/{entityType}', [EntityTypeController::class, 'destroy'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.destroy');

        Route::put('entity-types/{entityType}/activation', [EntityTypeActivationController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.activation.update');
        Route::put('entity-types/{entityType}/position', [EntityTypePositionController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.position.update');

        Route::get('vaults/{vault}/contacts', [ContactController::class, 'index'])->where('vault', '[0-9a-fA-F-]{36}')->name('contacts');
        Route::get('vaults/{vault}/contacts/{contact}', [ContactController::class, 'show'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.show');
        Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->where('vault', '[0-9a-fA-F-]{36}')->name('contacts.create');
        Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.destroy');

        Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.name.update');

        Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.favourite.update');
        Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.avatar.update');
        Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.avatar.destroy');
        Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactTemplateVersionController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.templateVersion.update');
    });
});
