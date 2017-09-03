<?php

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('index', 'Api\\APIDefaultController@index');

    // Contacts
    Route::resource('contacts', 'Api\\APIContactController');
});
