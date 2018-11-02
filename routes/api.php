<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('statistics', 'Api\\Statistics\\ApiStatisticsController', ['only' => ['index']]);

Route::resource('compliance', 'Api\\Settings\\ApiComplianceController', ['only' => ['index', 'show']]);

Route::resource('currencies', 'Api\\Settings\\ApiCurrencyController', ['only' => ['index', 'show']]);

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', 'Api\\ApiController@success');

    // Me
    Route::get('/me', 'Api\\Account\\ApiUserController@show');
    Route::get('/me/compliance', 'Api\\Account\\ApiUserController@compliance');
    Route::get('/me/compliance/{id}', 'Api\\Account\\ApiUserController@get');
    Route::post('/me/compliance', 'Api\\Account\\ApiUserController@set');

    // Contacts
    Route::apiResource('contacts', 'Api\\ApiContactController');

    // Relationships
    Route::apiResource('relationships', 'Api\\ApiRelationshipController', ['except' => ['index']]);
    Route::get('/contacts/{contact}/relationships', 'Api\\ApiRelationshipController@index');

    // Sets tags
    Route::post('/contacts/{contact}/setTags', 'Api\\ApiContactTagController@setTags');
    Route::get('/contacts/{contact}/unsetTags', 'Api\\ApiContactTagController@unsetTags');
    Route::post('/contacts/{contact}/unsetTag', 'Api\\ApiContactTagController@unsetTag');

    // Addresses
    Route::apiResource('addresses', 'Api\\ApiAddressController');
    Route::get('/contacts/{contact}/addresses', 'Api\\ApiAddressController@addresses');

    // Contact Fields
    Route::apiResource('contactfields', 'Api\\ApiContactFieldController', ['except' => ['index']]);
    Route::get('/contacts/{contact}/contactfields', 'Api\\ApiContactFieldController@contactFields');

    // Pets
    Route::apiResource('pets', 'Api\\ApiPetController');
    Route::get('/contacts/{contact}/pets', 'Api\\ApiPetController@pets');

    // Tags
    Route::apiResource('tags', 'Api\\ApiTagController');

    // Notes
    Route::apiResource('notes', 'Api\\ApiNoteController');
    Route::get('/contacts/{contact}/notes', 'Api\\ApiNoteController@notes');

    // Calls
    Route::apiResource('calls', 'Api\\ApiCallController');
    Route::get('/contacts/{contact}/calls', 'Api\\ApiCallController@calls');

    // Conversations & messages
    Route::apiResource('conversations', 'Api\\Contact\\ApiConversationController');
    Route::apiResource('conversations/{conversation}/messages', 'Api\\Contact\\ApiMessageController', ['except' => ['index', 'show']]);
    Route::get('/contacts/{contact}/conversations', 'Api\\Contact\\ApiConversationController@conversations');

    // Activities
    Route::apiResource('activities', 'Api\\ApiActivityController');
    Route::get('/contacts/{contact}/activities', 'Api\\ApiActivityController@activities');

    // Reminders
    Route::apiResource('reminders', 'Api\\ApiReminderController');
    Route::get('/contacts/{contact}/reminders', 'Api\\ApiReminderController@reminders');

    // Tasks
    Route::apiResource('tasks', 'Api\\ApiTaskController');
    Route::get('/contacts/{contact}/tasks', 'Api\\ApiTaskController@tasks');

    // Gifts
    Route::apiResource('gifts', 'Api\\ApiGiftController');
    Route::get('/contacts/{contact}/gifts', 'Api\\ApiGiftController@gifts');

    // Debts
    Route::apiResource('debts', 'Api\\ApiDebtController');
    Route::get('/contacts/{contact}/debts', 'Api\\ApiDebtController@debts');

    // Journal
    Route::apiResource('journal', 'Api\\ApiJournalController');

    // Activity Types
    Route::apiResource('activitytypes', 'Api\\Contact\\ApiActivityTypeController');

    // Activity Type Categories
    Route::apiResource('activitytypecategories', 'Api\\Contact\\ApiActivityTypeCategoryController');

    // Relationship Type Groups
    Route::apiResource('relationshiptypegroups', 'Api\\ApiRelationshipTypeGroupController', ['only' => [
      'index', 'show',
    ]]);

    // Relationship Types
    Route::apiResource('relationshiptypes', 'Api\\ApiRelationshipTypeController', ['only' => [
      'index', 'show',
    ]]);

    // Life events
    Route::apiResource('lifeevents', 'Api\\Contact\\ApiLifeEventController');

    // Documents
    Route::apiResource('documents', 'Api\\Contact\\ApiDocumentController', ['only' => [
      'index', 'show',
    ]]);
    Route::get('/contacts/{contact}/documents', 'Api\\Contact\\ApiDocumentController@documents');

    /*
     * SETTINGS
     */
    Route::apiResource('contactfieldtypes', 'Api\\Settings\\ApiContactFieldTypeController');

    /*
     * MISC
     */
    Route::get('/countries', 'Api\\Misc\\ApiCountryController@index');
});
