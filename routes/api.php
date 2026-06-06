<?php

use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
use App\Domains\Contact\ManageContact\Api\Controllers\ContactController;
use App\Domains\Contact\ManageContact\Api\Controllers\ContactTagController;
use App\Domains\Contact\ManageContact\Api\Controllers\TagController;
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

    // -------------------------------------------------------------------------
    // Tags — scoped to a vault
    //
    // GET    /api/vaults/{vault}/tags              → list with usage counts (cached)
    // POST   /api/vaults/{vault}/tags              → create tag
    // PUT    /api/vaults/{vault}/tags/{tag}        → update tag
    // DELETE /api/vaults/{vault}/tags/{tag}        → delete tag (+ optional reassign)
    // -------------------------------------------------------------------------
    Route::prefix('vaults/{vault}')->name('tags.')->group(function () {
        Route::apiResource('tags', TagController::class);
    });

    // -------------------------------------------------------------------------
    // Contacts — scoped to a vault
    //
    // GET /api/vaults/{vault}/contacts             → list (supports ?tags[]=1&tags[]=2)
    // -------------------------------------------------------------------------
    Route::prefix('vaults/{vault}')->name('vaults.')->group(function () {
        Route::apiResource('contacts', ContactController::class)->only(['index']);

        // -------------------------------------------------------------------------
        // Contact Tags
        //
        // POST   /api/vaults/{vault}/contacts/{contact}/tags         → attach tags
        // DELETE /api/vaults/{vault}/contacts/{contact}/tags/{tag}   → detach a tag
        // -------------------------------------------------------------------------
        Route::post(
            'contacts/{contact}/tags',
            [ContactTagController::class, 'store']
        )->name('contacts.tags.store');

        Route::delete(
            'contacts/{contact}/tags/{tag}',
            [ContactTagController::class, 'destroy']
        )->name('contacts.tags.destroy');
    });
});
