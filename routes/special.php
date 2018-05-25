<?php

use Illuminate\Support\Facades\Route;

Route::get('/settings/emailchange', 'Auth\EmailChangeController@showLoginFormSpecial');
Route::post('/settings/emailchange', 'Auth\EmailChangeController@login');

Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/settings/emailchange2', 'Auth\EmailChangeController@index');
    Route::post('/settings/emailchange2', 'Auth\EmailChangeController@save');
});
