<?php

Route::group(['middleware' => ['auth:api', 'throttle:60,1']], function () {
    Route::get('/', 'Api\\ApiController@success');

    // Contacts
    Route::resource('contacts', 'Api\\ApiContactController', ['except' => [
      'create', 'edit', 'patch',
    ]]);

    // Set a partner to the contact
    Route::post('/contacts/{contact}/partners', 'Api\\ApiContactController@partners');
    Route::post('/contacts/{contact}/partners/unset', 'Api\\ApiContactController@unsetPartners');

    // Set a kid to the contact
    Route::post('/contacts/{contact}/kids', 'Api\\ApiContactController@kids');
    Route::post('/contacts/{contact}/kids/unset', 'Api\\ApiContactController@unsetKids');

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
    Route::get('/activitytypes', 'Api\\ApiActivityController@activitytypes');

    // Reminders
    Route::resource('reminders', 'Api\\ApiReminderController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/reminders', 'Api\\ApiReminderController@reminders');

    // Tasks
    Route::resource('tasks', 'Api\\ApiTaskController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/tasks', 'Api\\ApiTaskController@tasks');

    // Gifts
    Route::resource('gifts', 'Api\\ApiGiftController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/gifts', 'Api\\ApiGiftController@gifts');

    // Debts
    Route::resource('debts', 'Api\\ApiDebtController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/debts', 'Api\\ApiDebtController@debts');

    // Debts
    Route::resource('journal', 'Api\\ApiJournalController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
});
