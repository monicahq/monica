<?php

use App\Domains\Contact\ManageContact\Api\Controllers\ContactImportController;
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

    // import
    Route::get('import', [ContactImportController::class, 'index']);
    Route::post('import', [ContactImportController::class, 'store']);
    Route::get('import/{importJob}/errors.csv', [ContactImportController::class, 'errorsCsv']);
    Route::get('import/{importJob}', [ContactImportController::class, 'show']);
});
