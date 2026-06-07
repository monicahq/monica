<?php

use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportController;

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

    // imports
    Route::prefix('import')->name('import.')->group(function () {
        Route::post('', [ImportController::class, 'store'])->name('store');
        Route::get('', [ImportController::class, 'index'])->name('index');
        Route::get('{id}', [ImportController::class, 'show'])->name('show');
        Route::post('{id}/cancel', [ImportController::class, 'cancel'])->name('cancel');
        Route::get('{id}/errors', [ImportController::class, 'errors'])->name('errors');
        Route::get('{id}/errors.csv', [ImportController::class, 'errorsCsv'])->name('errors.csv');
    });
});
