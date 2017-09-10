<?php

Route::group(['middleware' => ['auth:api']], function () {

    // Contacts
    Route::resource('contacts', 'Api\\ApiContactController', ['except' => [
      'create', 'edit',
    ]]);
});
