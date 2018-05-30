<?php

use Illuminate\Support\Facades\Route;

Route::get('/statistics', 'Api\\Statistics\\ApiStatisticsController@index');

Route::get('/compliance', 'Api\\Settings\\ApiComplianceController@index');
Route::get('/compliance/{id}', 'Api\\Settings\\ApiComplianceController@show');

Route::get('/currencies', 'Api\\Settings\\ApiCurrencyController@index');
Route::get('/currencies/{id}', 'Api\\Settings\\ApiCurrencyController@show');

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', 'Api\\ApiController@success');

    // Me
    Route::get('/me', 'Api\\Account\\ApiUserController@show');
    Route::get('/me/compliance', 'Api\\Account\\ApiUserController@compliance');
    Route::get('/me/compliance/{id}', 'Api\\Account\\ApiUserController@get');
    Route::post('/me/compliance', 'Api\\Account\\ApiUserController@set');

    // Contacts
    Route::resource('contacts', 'Api\\ApiContactController', ['except' => [
      'create', 'edit', 'patch',
    ]]);

    // Relationships
    Route::get('/contacts/{contact}/relationships', 'Api\\ApiRelationshipController@index');
    Route::get('/relationships/{id}', 'Api\\ApiRelationshipController@show');
    Route::post('/relationships', 'Api\\ApiRelationshipController@create');
    Route::put('/relationships/{id}', 'Api\\ApiRelationshipController@update');
    Route::delete('/relationships/{id}', 'Api\\ApiRelationshipController@destroy');

    // Sets tags
    Route::post('/contacts/{contact}/setTags', 'Api\\ApiContactTagController@setTags');
    Route::get('/contacts/{contact}/unsetTags', 'Api\\ApiContactTagController@unsetTags');
    Route::post('/contacts/{contact}/unsetTag', 'Api\\ApiContactTagController@unsetTag');

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

    // Relationship Type Groups
    Route::resource('relationshiptypegroups', 'Api\\ApiRelationshipTypeGroupController', ['except' => [
      'create', 'store', 'destroy', 'edit', 'patch', 'update',
    ]]);

    // Relationship Types
    Route::resource('relationshiptypes', 'Api\\ApiRelationshipTypeController', ['except' => [
      'create', 'store', 'destroy', 'edit', 'patch', 'update',
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
