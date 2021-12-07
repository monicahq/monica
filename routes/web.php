<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vault\VaultController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', 'Dashboard\\DashboardController@index')->name('dashboard');

    // vaults
    Route::get('vaults', [VaultController::class, 'index'])->name('vault.index');
    Route::get('vaults/new', [VaultController::class, 'new'])->name('vault.new');
    Route::post('vaults', [VaultController::class, 'store'])->name('vault.store');

    Route::middleware(['vault'])->prefix('vaults/{vault}')->group(function () {
        Route::get('', [VaultController::class, 'show'])->name('vault.show');
    });

    Route::get('contacts', 'ContactController@index');

    Route::get('settings', 'Settings\\SettingsController@index')->name('settings.index');
    Route::resource('settings/information', 'Settings\\InformationController');

    // contacts
    Route::get('vaults/{vault}/contacts/{contact}', 'HomeController@index')->name('contact.show');
});
