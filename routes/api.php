<?php

use App\Domains\Contact\ManageContact\Api\Controllers\ContactController;
use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
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

    // contacts
    Route::prefix('vaults/{vault}')->group(function () {
        Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/favorites', [ContactController::class, 'favorites'])->name('contacts.favorites');
        Route::get('contacts/stats', [ContactController::class, 'stats'])->name('contacts.stats');
        Route::get('contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::post('contacts/{contact}/favorite', [ContactController::class, 'markFavorite'])->name('contacts.favorite.mark');
        Route::delete('contacts/{contact}/favorite', [ContactController::class, 'removeFavorite'])->name('contacts.favorite.remove');
        Route::patch('contacts/{contact}/favorite', [ContactController::class, 'toggleFavorite'])->name('contacts.favorite.toggle');
        Route::put('contacts/{contact}/note', [ContactController::class, 'updateNote'])->name('contacts.note.update');
    });
});

