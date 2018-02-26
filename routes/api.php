<?php

Route::group(['middleware' => ['auth:api', 'throttle:60,1']], function () {
    Route::get('/', 'Api\\ApiController@success');

    /*
     * CONTACTS
     */
    Route::resource('contacts', 'Api\\ApiContactController', ['except' => [
      'create', 'edit', 'patch',
    ]]);

    // Sets tags
    Route::post('/contacts/{contact}/setTags', 'Api\\ApiContactTagController@setTags');
    Route::get('/contacts/{contact}/unsetTags', 'Api\\ApiContactTagController@unsetTags');
    Route::post('/contacts/{contact}/unsetTag', 'Api\\ApiContactTagController@unsetTag');

    // Set a partner to the contact
    Route::post('/contacts/{contact}/partners', 'Api\\ApiContactController@partners');
    Route::post('/contacts/{contact}/partners/unset', 'Api\\ApiContactController@unsetPartners');

    // Set a kid to the contact
    Route::post('/contacts/{contact}/kids', 'Api\\ApiContactController@kids');
    Route::post('/contacts/{contact}/kids/unset', 'Api\\ApiContactController@unsetKids');

    // Addresses
    Route::resource('addresses', 'Api\\ApiAddressController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/addresses', 'Api\\ApiAddressController@addresses');

    // Contact Fields
    Route::resource('contactfields', 'Api\\ApiContactFieldController', ['except' => [
      'create', 'edit', 'patch',
    ]]);
    Route::get('/contacts/{contact}/contactfields', 'Api\\ApiContactFieldController@contactFields');

    // Pets
    Route::resource('pets', 'Api\\ApiPetController');

    // Contact Pets
    Route::get('/contacts/{contact}/pets', 'Api\\ApiPetController@listContactPets');
    Route::post('/contacts/{contact}/pets', 'Api\\ApiPetController@storeContactPet');
    Route::put('/contacts/{contact}/pets/{pet}', 'Api\\ApiPetController@moveContactPet');

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

    /*
     * SETTINGS
     */
    Route::resource('contactfieldtypes', 'Api\\Settings\\ApiContactFieldTypeController', ['except' => [
      'create', 'edit', 'patch',
    ]]);

    /*
     * MISC
     */
    Route::get('/countries', 'Api\\Misc\\ApiCountryController@index');
});
