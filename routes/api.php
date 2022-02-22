<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('statistics', 'Statistics\\ApiStatisticsController', ['only' => ['index']])->name('index', 'api.statistics');

Route::resource('compliance', 'Settings\\ApiComplianceController', ['only' => ['index', 'show']]);

Route::resource('currencies', 'Settings\\ApiCurrencyController', ['only' => ['index', 'show']])->name('index', 'api.currencies');

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', 'ApiController@success')->name('api');
    Route::name('api.')->group(function () {
        // Me
        Route::get('/me', 'Account\\ApiUserController@show');
        Route::get('/me/compliance', 'Account\\ApiUserController@getSignedPolicies');
        Route::get('/me/compliance/{id}', 'Account\\ApiUserController@get');
        Route::post('/me/compliance', 'Account\\ApiUserController@set');

        // Contacts
        Route::apiResource('contacts', 'ApiContactController')
            ->names(['index' => 'contacts', 'show' => 'contact']);
        Route::post('/me/contact', 'ApiMeController@store');
        Route::delete('/me/contact', 'ApiMeController@destroy');

        // Contacts properties
        Route::put('/contacts/{contact}/work', 'ApiContactController@updateWork');
        Route::put('/contacts/{contact}/food', 'ApiContactController@updateFoodPreferences');
        Route::put('/contacts/{contact}/introduction', 'ApiContactController@updateIntroduction');

        // Genders
        Route::apiResource('genders', 'Account\\ApiGenderController');

        // Relationships
        Route::apiResource('relationships', 'ApiRelationshipController', ['except' => ['index']])
            ->names(['show' => 'relationship']);
        Route::get('/contacts/{contact}/relationships', 'ApiRelationshipController@index')
            ->name('relationships');

        // Sets tags
        Route::post('/contacts/{contact}/setTags', 'ApiContactTagController@setTags');
        Route::post('/contacts/{contact}/unsetTags', 'ApiContactTagController@unsetTags');
        Route::post('/contacts/{contact}/unsetTag', 'ApiContactTagController@unsetTag');

        // Places
        Route::apiResource('places', 'Account\\ApiPlaceController');

        // Addresses
        Route::apiResource('addresses', 'Contact\\ApiAddressController')
            ->names(['index' => 'addresses', 'show' => 'address']);
        Route::get('/contacts/{contact}/addresses', 'Contact\\ApiAddressController@addresses');

        // Contact Fields
        Route::apiResource('contactfields', 'ApiContactFieldController', ['except' => ['index']]);
        Route::get('/contacts/{contact}/contactfields', 'ApiContactFieldController@contactFields');

        // Pets
        Route::apiResource('pets', 'ApiPetController');
        Route::get('/contacts/{contact}/pets', 'ApiPetController@pets');

        // Tags
        Route::apiResource('tags', 'ApiTagController');
        Route::get('/tags/{tag}/contacts', 'ApiTagController@contacts');

        // Companies
        Route::apiResource('companies', 'Account\\ApiCompanyController');

        // Occupations
        Route::apiResource('occupations', 'Contact\\ApiOccupationController');

        // Notes
        Route::apiResource('notes', 'ApiNoteController')
            ->names(['index' => 'notes', 'show' => 'note']);
        Route::get('/contacts/{contact}/notes', 'ApiNoteController@notes');

        // Calls
        Route::apiResource('calls', 'Contact\\ApiCallController')
            ->names(['index' => 'calls', 'show' => 'call']);
        Route::get('/contacts/{contact}/calls', 'Contact\\ApiCallController@calls');

        // Conversations & messages
        Route::apiResource('conversations', 'Contact\\ApiConversationController')
            ->names(['index' => 'conversations', 'show' => 'conversation']);
        Route::apiResource('conversations/{conversation}/messages', 'Contact\\ApiMessageController', ['except' => ['index', 'show']]);
        Route::get('/contacts/{contact}/conversations', 'Contact\\ApiConversationController@conversations');

        // Activities
        Route::apiResource('activities', 'ApiActivitiesController')
            ->names(['index' => 'activities', 'show' => 'activity']);
        Route::get('/contacts/{contact}/activities', 'ApiActivitiesController@activities');

        // Reminders
        Route::get('reminders/upcoming/{month}', 'ApiReminderController@upcoming');
        Route::apiResource('reminders', 'ApiReminderController')
            ->names(['index' => 'reminders']);
        Route::get('/contacts/{contact}/reminders', 'ApiReminderController@reminders');

        // Tasks
        Route::apiResource('tasks', 'ApiTaskController');
        Route::get('/contacts/{contact}/tasks', 'ApiTaskController@tasks');

        // Gifts
        Route::apiResource('gifts', 'ApiGiftController');
        Route::get('/contacts/{contact}/gifts', 'ApiGiftController@gifts');
        Route::put('/gifts/{gift}/photo/{photo}', 'ApiGiftController@associate');

        // Debts
        Route::apiResource('debts', 'ApiDebtController');
        Route::get('/contacts/{contact}/debts', 'ApiDebtController@debts');

        // Journal
        Route::apiResource('journal', 'ApiJournalController')
            ->names(['index' => 'journal', 'show' => 'entry']);

        // Activity Types
        Route::apiResource('activitytypes', 'Account\\Activity\\ApiActivityTypeController');

        // Activity Type Categories
        Route::apiResource('activitytypecategories', 'Account\\Activity\\ApiActivityTypeCategoryController');

        // Relationship Type Groups
        Route::apiResource('relationshiptypegroups', 'ApiRelationshipTypeGroupController', ['only' => ['index', 'show']]);

        // Relationship Types
        Route::apiResource('relationshiptypes', 'ApiRelationshipTypeController', ['only' => ['index', 'show']]);

        // Life events
        Route::apiResource('lifeevents', 'Contact\\ApiLifeEventController');

        // Documents
        Route::apiResource('documents', 'Contact\\ApiDocumentController', ['except' => ['update']])
            ->names(['index' => 'documents', 'show' => 'document']);
        Route::get('/contacts/{contact}/documents', 'Contact\\ApiDocumentController@contact');

        // Photos
        Route::apiResource('photos', 'Contact\\ApiPhotoController', ['except' => ['update']])
            ->names(['index' => 'photos', 'show' => 'photo']);
        Route::get('/contacts/{contact}/photos', 'Contact\\ApiPhotoController@contact');

        // Avatars
        Route::put('/contacts/{contact}/avatar', 'Contact\\ApiAvatarController@update');

        // Contact logs
        Route::get('/contacts/{contact}/logs', 'Contact\\ApiAuditLogController@index');

        /*
         * SETTINGS
         */
        Route::apiResource('contactfieldtypes', 'Settings\\ApiContactFieldTypeController');
        Route::apiResource('logs', 'Settings\\ApiAuditLogController');

        /*
         * MISC
         */
        Route::get('/countries', 'Misc\\ApiCountryController@index')->name('countries');
    });
});
