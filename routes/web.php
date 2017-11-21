<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

if (App::environment('production')) {
    URL::forceScheme('https');
}

Route::get('/', 'Auth\LoginController@showLoginForm')->name('login');

Auth::routes();

Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

Route::get('/invitations/accept/{key}', 'SettingsController@acceptInvitation');
Route::post('/invitations/accept/{key}', 'SettingsController@storeAcceptedInvitation');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/logout', 'Auth\LoginController@logout');

    Route::get('/dashboard/', ['as' => 'dashboard', 'uses' => 'DashboardController@index']);

    Route::group(['as' => 'people'], function () {
        Route::get('/people/', 'PeopleController@index')->name('.index');
        Route::get('/people/add', 'PeopleController@create')->name('.create');
        Route::post('/people/', 'PeopleController@store')->name('.store');

        // Dashboard
        Route::get('/people/{contact}', 'PeopleController@show')->name('.show');
        Route::get('/people/{contact}/edit', 'PeopleController@edit')->name('.edit');
        Route::post('/people/{contact}/update', 'PeopleController@update')->name('.update');
        Route::delete('/people/{contact}', 'PeopleController@delete')->name('.delete');

        // Contact information
        Route::get('/people/{contact}/contactfield', 'People\\ContactFieldsController@getContactFields');
        Route::post('/people/{contact}/contactfield', 'People\\ContactFieldsController@storeContactField');
        Route::put('/people/{contact}/contactfield/{contact_field}', 'People\\ContactFieldsController@editContactField');
        Route::delete('/people/{contact}/contactfield/{contact_field}', 'People\\ContactFieldsController@destroyContactField');
        Route::get('/people/{contact}/contactfieldtypes', 'People\\ContactFieldsController@getContactFieldTypes');

        // Addresses
        Route::get('/people/{contact}/countries', 'People\\AddressesController@getCountries');
        Route::get('/people/{contact}/addresses', 'People\\AddressesController@get');
        Route::post('/people/{contact}/addresses', 'People\\AddressesController@store');
        Route::put('/people/{contact}/addresses/{address}', 'People\\AddressesController@edit');
        Route::delete('/people/{contact}/addresses/{address}', 'People\\AddressesController@destroy');

        // Work information
        Route::get('/people/{contact}/work/edit', ['as' => '.edit', 'uses' => 'PeopleController@editWork'])->name('.work.edit');
        Route::post('/people/{contact}/work/update', 'PeopleController@updateWork')->name('.work.update');

        // Introductions
        Route::get('/people/{contact}/introductions/edit', 'People\\IntroductionsController@edit')->name('.introductions.edit');
        Route::post('/people/{contact}/introductions/update', 'People\\IntroductionsController@update')->name('.introductions.update');

        // Tags
        Route::post('/people/{contact}/tags/update', 'People\\TagsController@update')->name('.tags.update');

        // Notes
        Route::get('/people/{contact}/notes/add', 'People\\NotesController@create')->name('.notes.add');
        Route::post('/people/{contact}/notes/store', 'People\\NotesController@store')->name('.notes.store');
        Route::get('/people/{contact}/notes/{note}/edit', 'People\\NotesController@edit')->name('.notes.edit');
        Route::put('/people/{contact}/notes/{note}', 'People\\NotesController@update')->name('.notes.update');
        Route::delete('/people/{contact}/notes/{note}', 'People\\NotesController@destroy')->name('.notes.delete');

        // Food preferencies
        Route::get('/people/{contact}/food', 'PeopleController@editFoodPreferencies')->name('.food');
        Route::post('/people/{contact}/food/save', 'PeopleController@updateFoodPreferencies')->name('.food.update');

        // Kid
        Route::get('/people/{contact}/kids/add', 'People\\KidsController@create')->name('.kids.add');
        Route::post('/people/{contact}/kids/store', 'People\\KidsController@store')->name('.kids.store');
        Route::post('/people/{contact}/kids/storeExistingContact', 'People\\KidsController@storeExistingContact')->name('.kids.storeexisting');
        Route::get('/people/{contact}/kids/{kid}/edit', 'People\\KidsController@edit')->name('.kids.edit');
        Route::put('/people/{contact}/kids/{kid}', 'People\\KidsController@update')->name('.kids.update');
        Route::delete('/people/{contact}/kids/{kid}', 'People\\KidsController@destroy')->name('.kids.delete');
        Route::post('/people/{contact}/kids/{kid}/unlink', 'People\\KidsController@unlink')->name('.kids.unlink');

        // Relationships (significant others)
        Route::get('/people/{contact}/relationships/add', 'People\\RelationshipsController@create')->name('.relationships.add');
        Route::post('/people/{contact}/relationships/store', 'People\\RelationshipsController@store')->name('.relationships.store');
        Route::post('/people/{contact}/relationships/storeExistingContact', 'People\\RelationshipsController@storeExistingContact')->name('.relationships.storeexisting');
        Route::get('/people/{contact}/relationships/{partner}/edit', 'People\\RelationshipsController@edit')->name('.relationships.edit');
        Route::put('/people/{contact}/relationships/{partner}', 'People\\RelationshipsController@update')->name('.relationships.update');
        Route::delete('/people/{contact}/relationships/{partner}', 'People\\RelationshipsController@destroy')->name('.relationships.delete');
        Route::post('/people/{contact}/relationships/{partner}/unlink', 'People\\RelationshipsController@unlink')->name('.relationships.unlink');

        // Reminders
        Route::get('/people/{contact}/reminders/add', 'People\\RemindersController@create')->name('.reminders.add');
        Route::post('/people/{contact}/reminders/store', 'People\\RemindersController@store')->name('.reminders.store');
        Route::get('/people/{contact}/reminders/{reminder}/edit', 'People\\RemindersController@edit')->name('.reminders.edit');
        Route::put('/people/{contact}/reminders/{reminder}', 'People\\RemindersController@update')->name('.reminders.update');
        Route::delete('/people/{contact}/reminders/{reminder}', 'People\\RemindersController@destroy')->name('.reminders.delete');

        // Tasks
        Route::get('/people/{contact}/tasks/add', 'People\\TasksController@create')->name('.tasks.add');
        Route::post('/people/{contact}/tasks/store', 'People\\TasksController@store')->name('.tasks.store');
        Route::patch('/people/{contact}/tasks/{task}/toggle', 'People\\TasksController@toggle')->name('.tasks.toggle');
        Route::delete('/people/{contact}/tasks/{task}', 'People\\TasksController@destroy')->name('.tasks.delete');

        // Gifts
        Route::get('/people/{contact}/gifts/add', 'People\\GiftsController@create')->name('.gifts.add');
        Route::post('/people/{contact}/gifts/store', 'People\\GiftsController@store')->name('.gifts.store');
        Route::delete('/people/{contact}/gifts/{gift}', 'People\\GiftsController@destroy')->name('.gifts.delete');

        // Debt
        Route::get('/people/{contact}/debt/add', 'People\\DebtController@create')->name('.debt.add');
        Route::post('/people/{contact}/debt/store', 'People\\DebtController@store')->name('.debt.store');
        Route::get('/people/{contact}/debt/{debt}/edit', 'People\\DebtController@edit')->name('.debt.edit');
        Route::put('/people/{contact}/debt/{debt}', 'People\\DebtController@update')->name('.debt.update');
        Route::delete('/people/{contact}/debt/{debt}', 'People\\DebtController@destroy')->name('.debt.delete');

        // Phone calls
        Route::post('/people/{contact}/call/store', 'People\\CallsController@store')->name('.call.store');
        Route::delete('/people/{contact}/call/{call}', 'People\\CallsController@destroy')->name('.call.delete');

        // Search
        Route::post('/people/search', 'PeopleController@search')->name('people.search');
    });

    // Activities
    Route::group(['as' => 'activities'], function () {
        Route::get('/activities/add/{contact}', 'ActivitiesController@create')->name('.add');
        Route::post('/activities/store/{contact}', 'ActivitiesController@store')->name('.store');
        Route::get('/activities/{activity}/edit/{contact}', 'ActivitiesController@edit')->name('.edit');
        Route::put('/activities/{activity}/{contact}', 'ActivitiesController@update')->name('.update');
        Route::delete('/activities/{activity}', 'ActivitiesController@destroy')->name('.delete');
    });

    Route::group(['as' => 'journal'], function () {
        Route::get('/journal', ['as' => '.index', 'uses' => 'JournalController@index']);
        Route::get('/journal/add', ['as' => '.create', 'uses' => 'JournalController@add']);
        Route::post('/journal/create', ['as' => '.create', 'uses' => 'JournalController@save']);
        Route::delete('/journal/{entryId}', ['as' => '.delete', 'uses' => 'JournalController@deleteEntry']);
    });

    Route::group(['as' => 'settings'], function () {
        Route::get('/settings', ['as' => '.index', 'uses' => 'SettingsController@index']);
        Route::post('/settings/delete', ['as' => '.delete', 'uses' => 'SettingsController@delete']);
        Route::post('/settings/reset', ['as' => '.reset', 'uses' => 'SettingsController@reset']);
        Route::post('/settings/save', 'SettingsController@save');
        Route::get('/settings/export', 'SettingsController@export')->name('.export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL');

        Route::get('/settings/personalization', 'Settings\\PersonalizationController@index')->name('.personalization');
        Route::get('/settings/personalization/contactfieldtypes', 'Settings\\PersonalizationController@getContactFieldTypes');
        Route::post('/settings/personalization/contactfieldtypes', 'Settings\\PersonalizationController@storeContactFieldType');
        Route::put('/settings/personalization/contactfieldtypes/{contactfieldtype_id}', 'Settings\\PersonalizationController@editContactFieldType');
        Route::delete('/settings/personalization/contactfieldtypes/{contactfieldtype_id}', 'Settings\\PersonalizationController@destroyContactFieldType');

        Route::get('/settings/import', 'SettingsController@import')->name('.import');
        Route::get('/settings/import/report/{importjobid}', 'SettingsController@report')->name('.report');
        Route::get('/settings/import/upload', 'SettingsController@upload')->name('.upload');
        Route::post('/settings/import/storeImport', 'SettingsController@storeImport')->name('.storeImport');

        Route::get('/settings/users', 'SettingsController@users')->name('.users');
        Route::get('/settings/users/add', 'SettingsController@addUser')->name('.users.add');
        Route::delete('/settings/users/{user}', ['as' => '.users.delete', 'uses' => 'SettingsController@deleteAdditionalUser']);
        Route::post('/settings/users/save', 'SettingsController@inviteUser')->name('.users.save');
        Route::delete('/settings/users/invitations/{invitation}', 'SettingsController@destroyInvitation');

        Route::get('/settings/subscriptions', 'Settings\\SubscriptionsController@index')->name('.subscriptions.index');
        Route::get('/settings/subscriptions/upgrade', 'Settings\\SubscriptionsController@upgrade')->name('.subscriptions.upgrade');
        Route::post('/settings/subscriptions/processPayment', 'Settings\\SubscriptionsController@processPayment');
        Route::get('/settings/subscriptions/invoice/{invoice}', 'Settings\\SubscriptionsController@downloadInvoice');
        Route::get('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@downgrade')->name('.subscriptions.downgrade');
        Route::post('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@processDowngrade');

        Route::get('/settings/tags', 'SettingsController@tags')->name('.tags');
        Route::get('/settings/tags/add', 'SettingsController@addUser')->name('.tags.add');
        Route::delete('/settings/tags/{user}', ['as' => '.tags.delete', 'uses' => 'SettingsController@deleteTag']);

        Route::get('/settings/api', 'SettingsController@api')->name('.api');
    });
});
