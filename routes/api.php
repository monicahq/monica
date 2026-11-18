<?php

use App\Domains\BlackBookd\Api\Controllers\BlackBookdContactProfileController;
use App\Domains\BlackBookd\Api\Controllers\BlackBookdDatingPipelineController;
use App\Domains\BlackBookd\Api\Controllers\BlackBookdEncounterController;
use App\Domains\BlackBookd\Api\Controllers\BlackBookdHealthLogController;
use App\Domains\BlackBookd\Api\Controllers\BlackBookdPartnerNotificationController;
use App\Domains\BlackBookd\Api\Controllers\BlackBookdSettingsController;
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

    // BlackBookd (private module; requires account toggle + sanctum token)
    Route::get('blackbookd/settings', [BlackBookdSettingsController::class, 'show'])->name('blackbookd.settings.show');
    Route::put('blackbookd/settings', [BlackBookdSettingsController::class, 'update'])->middleware('abilities:write')->name('blackbookd.settings.update');
    Route::get('blackbookd/export', [BlackBookdSettingsController::class, 'export'])->name('blackbookd.export');

    Route::middleware('blackbookd.enabled')->prefix('vaults/{vault}/blackbookd')->name('blackbookd.')->group(function () {
        Route::apiResource('encounters', BlackBookdEncounterController::class);
        Route::apiResource('health-logs', BlackBookdHealthLogController::class);

        Route::get('contact-profiles', [BlackBookdContactProfileController::class, 'index'])->name('contact_profiles.index');
        Route::get('contacts/{contact}/profile', [BlackBookdContactProfileController::class, 'show'])->name('contact_profiles.show');
        Route::put('contacts/{contact}/profile', [BlackBookdContactProfileController::class, 'update'])->name('contact_profiles.update');

        Route::get('dating-pipelines/quiet', [BlackBookdDatingPipelineController::class, 'quiet'])->name('dating_pipelines.quiet');
        Route::get('dating-pipelines/weekly-roundup', [BlackBookdDatingPipelineController::class, 'weeklyRoundup'])->name('dating_pipelines.weekly_roundup');
        Route::put('dating-pipelines/by-handle', [BlackBookdDatingPipelineController::class, 'upsertByHandle'])->name('dating_pipelines.upsert_by_handle');
        Route::apiResource('dating-pipelines', BlackBookdDatingPipelineController::class);

        Route::get('partner-notification', [BlackBookdPartnerNotificationController::class, 'index'])->name('partner_notification.index');
    });
});
