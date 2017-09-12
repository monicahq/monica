<?php

Route::group(['middleware' => ['auth:api', 'throttle:60,1']], function () {

    // Contacts
    Route::resource('contacts', 'Api\\ApiContactController', ['except' => [
      'create', 'edit',
    ]]);
});
