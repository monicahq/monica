<?php

Route::group(['middleware' => ['auth:api', 'throttle:60,1']], function () {

    // Contacts
    Route::resource('contacts', 'Api\\ApiContactController', ['except' => [
      'create', 'edit', 'patch',
    ]]);

    // Tags
    Route::resource('tags', 'Api\\ApiTagController', ['except' => [
      'create', 'edit', 'patch',
    ]]);

    // Notes
    Route::resource('notes', 'Api\\ApiNoteController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/notes', 'Api\\ApiNoteController@notes');

    // Calls
    Route::resource('calls', 'Api\\ApiCallController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/calls', 'Api\\ApiCallController@calls');

    // Activities
    Route::resource('activities', 'Api\\ApiActivityController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/activities', 'Api\\ApiActivityController@activities');
});
