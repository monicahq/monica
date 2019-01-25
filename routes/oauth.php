<?php

use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', 'Auth\\OAuthController@login');
});
