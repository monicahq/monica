<?php

use App\Domains\Contact\ManageContact\Api\Controllers\ContactController;
use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
use App\Domains\Vault\ManageTags\Api\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the bootstrap/app.php file and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    // users
    Route::get('user', [UserController::class, 'user']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']);

    // vaults
    Route::apiResource('vaults', VaultController::class);

    // tags
    Route::prefix('vaults/{vaultId}')->group(function () {
        Route::apiResource('tags', TagController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        
        // Contact tag endpoints
        Route::post('contacts/{contactId}/tags', [TagController::class, 'attachTag'])->name('tags.attach');
        Route::delete('contacts/{contactId}/tags/{tagId}', [TagController::class, 'detachTag'])->name('tags.detach');
    });

    // contacts with tag filtering
    Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
});

