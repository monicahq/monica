<?php

use Illuminate\Support\Facades\Route;

Route::get('/settings/emailchange', 'Auth\EmailChangeController@index');
Route::middleware(['2fa'])->group(function () {
    Route::post('/settings/emailchange', 'Auth\EmailChangeController@save');
});
