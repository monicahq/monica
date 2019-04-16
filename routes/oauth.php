<?php

use Illuminate\Support\Facades\Route;

Route::middleware('oauth')->group(function () {
    Route::get('/login', 'Auth\\OAuthController@index');
    Route::post('/login', 'Auth\\OAuthController@login')->name('oauth.login');

    Route::middleware(['auth', 'mfa'])->group(function () {
        Route::post('/verified', 'Auth\\OAuthController@verify')->name('oauth.verify');
    });

    Route::middleware(['auth', '2fa'])->group(function () {
        Route::post('/validate2fa', '\\App\\Http\\Controllers\\Auth\\Validate2faController@index')->name('oauth.validate2fa');
    });
});
