<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('statistics', 'Statistics\\ApiStatisticsController', ['only' => ['index']]);

Route::resource('compliance', 'Settings\\ApiComplianceController', ['only' => ['index', 'show']]);

Route::resource('currencies', 'Settings\\ApiCurrencyController', ['only' => ['index', 'show']]);

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', 'ApiController@success');

    // Me
    Route::get('/me', 'Account\\ApiUserController@show');
    Route::get('/me/compliance', 'Account\\ApiUserController@compliance');
    Route::get('/me/compliance/{id}', 'Account\\ApiUserController@get');
    Route::post('/me/compliance', 'Account\\ApiUserController@set');

    // Contacts
    Route::apiResource('contacts', 'ApiContactController');

    // Genders
    Route::apiResource('genders', 'Account\\ApiGenderController');

    // Relationships
    Route::apiResource('relationships', 'ApiRelationshipController', ['except' => ['index']]);
    Route::get('/contacts/{contact}/relationships', 'ApiRelationshipController@index');

    // Sets tags
    Route::post('/contacts/{contact}/setTags', 'ApiContactTagController@setTags');
    Route::post('/contacts/{contact}/unsetTags', 'ApiContactTagController@unsetTags');
    Route::post('/contacts/{contact}/unsetTag', 'ApiContactTagController@unsetTag');

    // Places
    Route::apiResource('places', 'Account\\ApiPlaceController');

    // Addresses
    Route::apiResource('addresses', 'Contact\\ApiAddressController');
    Route::get('/contacts/{contact}/addresses', 'Contact\\ApiAddressController@addresses');

    // Contact Fields
    Route::apiResource('contactfields', 'ApiContactFieldController', ['except' => ['index']]);
    Route::get('/contacts/{contact}/contactfields', 'ApiContactFieldController@contactFields');

    // Pets
    Route::apiResource('pets', 'ApiPetController');
    Route::get('/contacts/{contact}/pets', 'ApiPetController@pets');

    // Tags
    Route::apiResource('tags', 'ApiTagController');

    // Companies
    Route::apiResource('companies', 'Account\\ApiCompanyController');

    // Companies
    Route::apiResource('occupations', 'Contact\\ApiOccupationController');

    // Notes
    Route::apiResource('notes', 'ApiNoteController');
    Route::get('/contacts/{contact}/notes', 'ApiNoteController@notes');

    // Calls
    Route::apiResource('calls', 'Contact\\ApiCallController');
    Route::get('/contacts/{contact}/calls', 'Contact\\ApiCallController@calls');

    // Conversations & messages
    Route::apiResource('conversations', 'Contact\\ApiConversationController');
    Route::apiResource('conversations/{conversation}/messages', 'Contact\\ApiMessageController', ['except' => ['index', 'show']]);
    Route::get('/contacts/{contact}/conversations', 'Contact\\ApiConversationController@conversations');

    // Activities
    Route::apiResource('activities', 'ApiActivityController');
    Route::get('/contacts/{contact}/activities', 'ApiActivityController@activities');

    // Reminders
    Route::apiResource('reminders', 'ApiReminderController');
    Route::get('/contacts/{contact}/reminders', 'ApiReminderController@reminders');

    // Tasks
    Route::apiResource('tasks', 'ApiTaskController');
    Route::get('/contacts/{contact}/tasks', 'ApiTaskController@tasks');

    // Gifts
    Route::apiResource('gifts', 'ApiGiftController');
    Route::get('/contacts/{contact}/gifts', 'ApiGiftController@gifts');

    // Debts
    Route::apiResource('debts', 'ApiDebtController');
    Route::get('/contacts/{contact}/debts', 'ApiDebtController@debts');

    // Journal
    Route::apiResource('journal', 'ApiJournalController');

    // Activity Types
    Route::apiResource('activitytypes', 'Contact\\ApiActivityTypeController');

    // Activity Type Categories
    Route::apiResource('activitytypecategories', 'Contact\\ApiActivityTypeCategoryController');

    // Relationship Type Groups
    Route::apiResource('relationshiptypegroups', 'ApiRelationshipTypeGroupController', ['only' => [
      'index', 'show',
    ]]);

    // Relationship Types
    Route::apiResource('relationshiptypes', 'ApiRelationshipTypeController', ['only' => [
      'index', 'show',
    ]]);

    // Life events
    Route::apiResource('lifeevents', 'Contact\\ApiLifeEventController');

    // Documents
    Route::apiResource('documents', 'Contact\\ApiDocumentController', ['only' => [
      'index', 'show',
    ]]);
    Route::get('/contacts/{contact}/documents', 'Contact\\ApiDocumentController@documents');

    /*
     * SETTINGS
     */
    Route::apiResource('contactfieldtypes', 'Settings\\ApiContactFieldTypeController');

    /*
     * MISC
     */
    Route::get('/countries', 'Misc\\ApiCountryController@index');
});
