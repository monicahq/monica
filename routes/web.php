<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;

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

    Route::get('home', 'HomeController@index')->name('home');
    Route::get('contacts', 'ContactController@index');

    Route::get('settings', 'Settings\\SettingsController@index')->name('settings.index');
    Route::resource('settings/information', 'Settings\\InformationController');

    // vaults
    Route::get('vaults/{vault}', 'HomeController@index')->name('vault.show');

    // contacts
    Route::get('vaults/{vault}/contacts/{contact}', 'HomeController@index')->name('contact.show');
});
