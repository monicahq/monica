<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;

Route::apiResource('statistics', Statistics\ApiStatisticsController::class, ['only' => ['index']])->name('index', 'api.statistics');

Route::resource('compliance', Settings\ApiComplianceController::class, ['only' => ['index', 'show']]);

Route::resource('currencies', Settings\ApiCurrencyController::class, ['only' => ['index', 'show']])->name('index', 'api.currencies');

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('/', [ApiController::class, 'success'])->name('api');
    Route::name('api.')->group(function () {
        // Me
        Route::get('/me', [Account\ApiUserController::class, 'show']);
        Route::get('/me/compliance', [Account\ApiUserController::class, 'compliance']);
        Route::get('/me/compliance/{id}', [Account\ApiUserController::class, 'get']);
        Route::post('/me/compliance', [Account\ApiUserController::class, 'set']);

        // Contacts
        Route::apiResource('contacts', ApiContactController::class)
            ->names(['index' => 'contacts', 'show' => 'contact']);
        Route::put('/contacts/{contact}/setMe', [ApiContactController::class, 'setMe']);

        // Contacts properties
        Route::put('/contacts/{contact}/work', [ApiContactController::class, 'updateWork']);
        Route::put('/contacts/{contact}/food', [ApiContactController::class, 'updateFoodPreferences']);
        Route::put('/contacts/{contact}/introduction', [ApiContactController::class, 'updateIntroduction']);

        // Genders
        Route::apiResource('genders', Account\ApiGenderController::class);

        // Relationships
        Route::apiResource('relationships', ApiRelationshipController::class, ['except' => ['index']])
            ->names(['show' => 'relationship']);
        Route::get('/contacts/{contact}/relationships', [ApiRelationshipController::class, 'index'])
            ->name('relationships');

        // Sets tags
        Route::post('/contacts/{contact}/setTags', [ApiContactTagController::class, 'setTags']);
        Route::post('/contacts/{contact}/unsetTags', [ApiContactTagController::class, 'unsetTags']);
        Route::post('/contacts/{contact}/unsetTag', [ApiContactTagController::class, 'unsetTag']);

        // Places
        Route::apiResource('places', Account\ApiPlaceController::class);

        // Addresses
        Route::apiResource('addresses', Contact\ApiAddressController::class)
            ->names(['index' => 'addresses', 'show' => 'address']);
        Route::get('/contacts/{contact}/addresses', [Contact\ApiAddressController::class, 'addresses']);

        // Contact Fields
        Route::apiResource('contactfields', ApiContactFieldController::class, ['except' => ['index']]);
        Route::get('/contacts/{contact}/contactfields', [ApiContactFieldController::class, 'contactFields']);

        // Pets
        Route::apiResource('pets', ApiPetController::class);
        Route::get('/contacts/{contact}/pets', [ApiPetController::class, 'pets']);

        // Tags
        Route::apiResource('tags', ApiTagController::class);

        // Companies
        Route::apiResource('companies', Account\ApiCompanyController::class);

        // Occupations
        Route::apiResource('occupations', Contact\ApiOccupationController::class);

        // Notes
        Route::apiResource('notes', ApiNoteController::class)
            ->names(['index' => 'notes', 'show' => 'note']);
        Route::get('/contacts/{contact}/notes', [ApiNoteController::class, 'notes']);

        // Calls
        Route::apiResource('calls', Contact\ApiCallController::class)
            ->names(['index' => 'calls', 'show' => 'call']);
        Route::get('/contacts/{contact}/calls', [Contact\ApiCallController::class, 'calls']);

        // Conversations & messages
        Route::apiResource('conversations', Contact\ApiConversationController::class)
            ->names(['index' => 'conversations', 'show' => 'conversation']);
        Route::apiResource('conversations/{conversation}/messages', Contact\ApiMessageController::class, ['except' => ['index', 'show']]);
        Route::get('/contacts/{contact}/conversations', [Contact\ApiConversationController::class, 'conversations']);

        // Activities
        Route::apiResource('activities', ApiActivitiesController::class)
            ->names(['index' => 'activities', 'show' => 'activity']);
        Route::get('/contacts/{contact}/activities', [ApiActivitiesController::class, 'activities']);

        // Reminders
        Route::apiResource('reminders', ApiReminderController::class);
        Route::get('/contacts/{contact}/reminders', [ApiReminderController::class, 'reminders']);

        // Tasks
        Route::apiResource('tasks', ApiTaskController::class);
        Route::get('/contacts/{contact}/tasks', [ApiTaskController::class, 'tasks']);

        // Gifts
        Route::apiResource('gifts', ApiGiftController::class);
        Route::get('/contacts/{contact}/gifts', [ApiGiftController::class, 'gifts']);
        Route::put('/gifts/{gift}/photo/{photo}', [ApiGiftController::class, 'associate']);

        // Debts
        Route::apiResource('debts', ApiDebtController::class);
        Route::get('/contacts/{contact}/debts', [ApiDebtController::class, 'debts']);

        // Journal
        Route::apiResource('journal', ApiJournalController::class)
            ->names(['index' => 'journal', 'show' => 'entry']);

        // Activity Types
        Route::apiResource('activitytypes', Account\Activity\ApiActivityTypeController::class);

        // Activity Type Categories
        Route::apiResource('activitytypecategories', Account\Activity\ApiActivityTypeCategoryController::class);

        // Relationship Type Groups
        Route::apiResource('relationshiptypegroups', ApiRelationshipTypeGroupController::class, ['only' => ['index', 'show']]);

        // Relationship Types
        Route::apiResource('relationshiptypes', ApiRelationshipTypeController::class, ['only' => ['index', 'show']]);

        // Life events
        Route::apiResource('lifeevents', Contact\ApiLifeEventController::class);

        // Documents
        Route::apiResource('documents', Contact\ApiDocumentController::class, ['except' => ['update']])
            ->names(['index' => 'documents', 'show' => 'document']);
        Route::get('/contacts/{contact}/documents', [Contact\ApiDocumentController::class, 'contact']);

        // Photos
        Route::apiResource('photos', Contact\ApiPhotoController::class, ['except' => ['update']])
            ->names(['index' => 'photos', 'show' => 'photo']);
        Route::get('/contacts/{contact}/photos', [Contact\ApiPhotoController::class, 'contact']);

        // Avatars
        Route::put('/contacts/{contact}/avatar', [Contact\ApiAvatarController::class, 'update']);

        /*
         * SETTINGS
         */
        Route::apiResource('contactfieldtypes', Settings\ApiContactFieldTypeController::class);

        /*
         * MISC
         */
        Route::get('/countries', [Misc\ApiCountryController::class, 'index'])->name('countries');
    });
});
