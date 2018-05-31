<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::get('/', 'Auth\LoginController@showLoginOrRegister')->name('login');

Auth::routes();

Route::get('/password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');

Route::get('/invitations/accept/{key}', 'SettingsController@acceptInvitation');
Route::post('/invitations/accept/{key}', 'SettingsController@storeAcceptedInvitation');

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::post('/validate2fa', 'Auth\Validate2faController@index');
});

Route::middleware(['auth', 'auth.confirm', '2fa'])->group(function () {
    Route::group(['as' => 'dashboard'], function () {
        Route::get('/dashboard', 'DashboardController@index')->name('.index');
        Route::get('/dashboard/calls', 'DashboardController@calls');
        Route::get('/dashboard/notes', 'DashboardController@notes');
        Route::get('/dashboard/debts', 'DashboardController@debts');
        Route::post('/dashboard/setTab', 'DashboardController@setTab');
    });

    Route::get('/compliance', 'ComplianceController@index')->name('compliance');
    Route::post('/compliance/sign', 'ComplianceController@store');
    Route::get('/changelog', 'ChangelogController@index');

    Route::group(['as' => 'people'], function () {
        Route::get('/people', 'ContactsController@index')->name('.index');
        Route::get('/people/add', 'ContactsController@create')->name('.create');
        Route::get('/people/notfound', 'ContactsController@missing')->name('.missing');
        Route::post('/people', 'ContactsController@store')->name('.store');

        // Dashboard
        Route::get('/people/{contact}', 'ContactsController@show')->name('.show');
        Route::get('/people/{contact}/edit', 'ContactsController@edit')->name('.edit');
        Route::post('/people/{contact}/update', 'ContactsController@update')->name('.update');
        Route::delete('/people/{contact}', 'ContactsController@delete')->name('.delete');

        // Contact information
        Route::get('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@getContactFields');
        Route::post('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@storeContactField');
        Route::put('/people/{contact}/contactfield/{contact_field}', 'Contacts\\ContactFieldsController@editContactField');
        Route::delete('/people/{contact}/contactfield/{contact_field}', 'Contacts\\ContactFieldsController@destroyContactField');
        Route::get('/people/{contact}/contactfieldtypes', 'Contacts\\ContactFieldsController@getContactFieldTypes');

        // Export as vCard
        Route::get('/people/{contact}/vcard', 'ContactsController@vcard');

        // Addresses
        Route::get('/countries', 'Contacts\\AddressesController@getCountries');
        Route::get('/people/{contact}/addresses', 'Contacts\\AddressesController@get');
        Route::post('/people/{contact}/addresses', 'Contacts\\AddressesController@store');
        Route::put('/people/{contact}/addresses/{address}', 'Contacts\\AddressesController@edit');
        Route::delete('/people/{contact}/addresses/{address}', 'Contacts\\AddressesController@destroy');

        // Work information
        Route::get('/people/{contact}/work/edit', ['as' => '.edit', 'uses' => 'ContactsController@editWork'])->name('.work.edit');
        Route::post('/people/{contact}/work/update', 'ContactsController@updateWork')->name('.work.update');

        // Introductions
        Route::get('/people/{contact}/introductions/edit', 'Contacts\\IntroductionsController@edit')->name('.introductions.edit');
        Route::post('/people/{contact}/introductions/update', 'Contacts\\IntroductionsController@update')->name('.introductions.update');

        // Tags
        Route::post('/people/{contact}/tags/update', 'Contacts\\TagsController@update')->name('.tags.update');

        // Notes
        Route::get('/people/{contact}/notes', 'Contacts\\NotesController@get');
        Route::post('/people/{contact}/notes', 'Contacts\\NotesController@store')->name('.notes.store');
        Route::put('/people/{contact}/notes/{note}', 'Contacts\\NotesController@update');
        Route::delete('/people/{contact}/notes/{note}', 'Contacts\\NotesController@destroy');
        Route::post('/people/{contact}/notes/{note}/toggle', 'Contacts\\NotesController@toggle');

        // Food preferencies
        Route::get('/people/{contact}/food', 'ContactsController@editFoodPreferencies')->name('.food');
        Route::post('/people/{contact}/food/save', 'ContactsController@updateFoodPreferencies')->name('.food.update');

        // Relationships
        Route::get('/people/{contact}/relationships/new', 'Contacts\\RelationshipsController@new');
        Route::post('/people/{contact}/relationships/store', 'Contacts\\RelationshipsController@store')->name('.relationships.store');
        Route::get('/people/{contact}/relationships/{otherContact}/edit', 'Contacts\\RelationshipsController@edit')->name('.relationships.edit');
        Route::post('/people/{contact}/relationships/{otherContact}', 'Contacts\\RelationshipsController@update')->name('.relationships.update');
        Route::delete('/people/{contact}/relationships/{otherContact}', 'Contacts\\RelationshipsController@destroy')->name('.relationships.delete');

        // Pets
        Route::get('/people/{contact}/pets', 'Contacts\\PetsController@get');
        Route::post('/people/{contact}/pet', 'Contacts\\PetsController@store');
        Route::put('/people/{contact}/pet/{pet}', 'Contacts\\PetsController@update');
        Route::delete('/people/{contact}/pet/{pet}', 'Contacts\\PetsController@trash');
        Route::get('/petcategories', 'Contacts\\PetsController@getPetCategories');

        // Reminders
        Route::get('/people/{contact}/reminders/add', 'Contacts\\RemindersController@create')->name('.reminders.add');
        Route::post('/people/{contact}/reminders/store', 'Contacts\\RemindersController@store')->name('.reminders.store');
        Route::get('/people/{contact}/reminders/{reminder}/edit', 'Contacts\\RemindersController@edit')->name('.reminders.edit');
        Route::put('/people/{contact}/reminders/{reminder}', 'Contacts\\RemindersController@update')->name('.reminders.update');
        // Special route to delete reminders. In one migration in summer '17, I
        // accidentely f**ked up the reminders table by messing up the contact ids
        // and now the only way to delete those reminders is to bypass the ReminderRequest
        // by creating a new route.
        Route::delete('/people/{contact}/reminders/{rmd}', 'Contacts\\RemindersController@destroy')->name('.reminders.delete');

        // Tasks
        Route::get('/people/{contact}/tasks', 'Contacts\\TasksController@get');
        Route::post('/people/{contact}/tasks', 'Contacts\\TasksController@store');
        Route::post('/people/{contact}/tasks/{task}/toggle', 'Contacts\\TasksController@toggle');
        Route::put('/people/{contact}/tasks/{task}', 'Contacts\\TasksController@update');
        Route::delete('/people/{contact}/tasks/{task}', 'Contacts\\TasksController@destroy')->name('.tasks.delete');

        // Gifts
        Route::get('/people/{contact}/gifts', 'Contacts\\GiftsController@get');
        Route::post('/people/{contact}/gifts/{gift}/toggle', 'Contacts\\GiftsController@toggle');
        Route::get('/people/{contact}/gifts/add', 'Contacts\\GiftsController@create')->name('.gifts.add');
        Route::get('/people/{contact}/gifts/{gift}/edit', 'Contacts\\GiftsController@edit');
        Route::post('/people/{contact}/gifts/store', 'Contacts\\GiftsController@store')->name('.gifts.store');
        Route::post('/people/{contact}/gifts/{gift}/update', 'Contacts\\GiftsController@update')->name('.gifts.update');
        Route::delete('/people/{contact}/gifts/{gift}', 'Contacts\\GiftsController@destroy')->name('.gifts.delete');

        // Debt
        Route::get('/people/{contact}/debt/add', 'Contacts\\DebtController@create')->name('.debt.add');
        Route::post('/people/{contact}/debt/store', 'Contacts\\DebtController@store')->name('.debt.store');
        Route::get('/people/{contact}/debt/{debt}/edit', 'Contacts\\DebtController@edit')->name('.debt.edit');
        Route::put('/people/{contact}/debt/{debt}', 'Contacts\\DebtController@update')->name('.debt.update');
        Route::delete('/people/{contact}/debt/{debt}', 'Contacts\\DebtController@destroy')->name('.debt.delete');

        // Phone calls
        Route::post('/people/{contact}/call/store', 'Contacts\\CallsController@store')->name('.call.store');
        Route::delete('/people/{contact}/call/{call}', 'Contacts\\CallsController@destroy')->name('.call.delete');

        // Search
        Route::post('/people/search', 'ContactsController@search')->name('people.search');

        // Stay in touch information
        Route::post('/people/{contact}/stayintouch', 'ContactsController@stayInTouch');
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
        Route::get('/journal/entries', 'JournalController@list')->name('.list');
        Route::get('/journal/entries/{journalEntry}', 'JournalController@get');
        Route::get('/journal/hasRated', 'JournalController@hasRated');
        Route::post('/journal/day', 'JournalController@storeDay');
        Route::delete('/journal/day/{day}', 'JournalController@trashDay');

        Route::get('/journal/add', ['as' => '.create', 'uses' => 'JournalController@create']);
        Route::post('/journal/create', ['as' => '.create', 'uses' => 'JournalController@save']);
        Route::delete('/journal/{entryId}', 'JournalController@deleteEntry');
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

        Route::get('/settings/personalization/genders', 'Settings\\GendersController@getGenderTypes');
        Route::post('/settings/personalization/genders', 'Settings\\GendersController@storeGender');
        Route::put('/settings/personalization/genders/{gender}', 'Settings\\GendersController@updateGender');
        Route::delete('/settings/personalization/genders/{gender}/replaceby/{gender_id}', 'Settings\\GendersController@destroyAndReplaceGender');
        Route::delete('/settings/personalization/genders/{gender}', 'Settings\\GendersController@destroyGender');

        Route::get('/settings/personalization/reminderrules', 'Settings\\ReminderRulesController@get');
        Route::post('/settings/personalization/reminderrules/{reminderRule}', 'Settings\\ReminderRulesController@toggle');

        Route::get('/settings/personalization/modules', 'Settings\\ModulesController@get');
        Route::post('/settings/personalization/modules/{module}', 'Settings\\ModulesController@toggle');

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
        Route::get('/settings/subscriptions/upgrade/success', 'Settings\\SubscriptionsController@upgradeSuccess')->name('.subscriptions.upgrade.success');
        Route::post('/settings/subscriptions/processPayment', 'Settings\\SubscriptionsController@processPayment');
        Route::get('/settings/subscriptions/invoice/{invoice}', 'Settings\\SubscriptionsController@downloadInvoice');
        Route::get('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@downgrade')->name('.subscriptions.downgrade');
        Route::post('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@processDowngrade');
        Route::get('/settings/subscriptions/downgrade/success', 'Settings\\SubscriptionsController@downgradeSuccess')->name('.subscriptions.upgrade.success');

        Route::get('/settings/tags', 'SettingsController@tags')->name('.tags');
        Route::get('/settings/tags/add', 'SettingsController@addUser')->name('.tags.add');
        Route::delete('/settings/tags/{user}', ['as' => '.tags.delete', 'uses' => 'SettingsController@deleteTag']);

        Route::get('/settings/api', 'SettingsController@api')->name('.api');

        // Security
        Route::get('/settings/security', 'SettingsController@security')->name('.security');
        Route::post('/settings/security/passwordChange', 'Auth\\PasswordChangeController@passwordChange');
        Route::get('/settings/security/2fa-enable', 'Settings\\MultiFAController@enableTwoFactor')->name('.security.2fa-enable');
        Route::post('/settings/security/2fa-enable', 'Settings\\MultiFAController@validateTwoFactor');
        Route::get('/settings/security/2fa-disable', 'Settings\\MultiFAController@disableTwoFactor')->name('.security.2fa-disable');
        Route::post('/settings/security/2fa-disable', 'Settings\\MultiFAController@deactivateTwoFactor');
    });
});
