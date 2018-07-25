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

Route::get('/invitations/accept/{key}', 'SettingsController@acceptInvitation');
Route::post('/invitations/accept/{key}', 'SettingsController@storeAcceptedInvitation')->name('invitations.accept');

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::post('/validate2fa', 'Auth\Validate2faController@index');
});

Route::middleware(['auth', 'auth.confirm', 'u2f', '2fa'])->group(function () {
    Route::name('dashboard.')->group(function () {
        Route::get('/dashboard', 'DashboardController@index')->name('index');
        Route::get('/dashboard/calls', 'DashboardController@calls');
        Route::get('/dashboard/notes', 'DashboardController@notes');
        Route::get('/dashboard/debts', 'DashboardController@debts');
        Route::post('/dashboard/setTab', 'DashboardController@setTab');
    });

    Route::get('/compliance', 'ComplianceController@index')->name('compliance');
    Route::post('/compliance/sign', 'ComplianceController@store');
    Route::get('/changelog', 'ChangelogController@index')->name('changelog.index');

    Route::name('people.')->group(function () {
        Route::get('/people', 'ContactsController@index')->name('index');
        Route::get('/people/add', 'ContactsController@create')->name('create');
        Route::get('/people/notfound', 'ContactsController@missing')->name('missing');
        Route::post('/people', 'ContactsController@store')->name('store');

        // Dashboard
        Route::get('/people/{contact}', 'ContactsController@show')->name('show');
        Route::get('/people/{contact}/edit', 'ContactsController@edit')->name('edit');
        Route::post('/people/{contact}/update', 'ContactsController@update')->name('update');
        Route::delete('/people/{contact}', 'ContactsController@delete')->name('delete');

        // Contact information
        Route::get('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@getContactFields');
        Route::post('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@storeContactField');
        Route::put('/people/{contact}/contactfield/{contact_field}', 'Contacts\\ContactFieldsController@editContactField');
        Route::delete('/people/{contact}/contactfield/{contact_field}', 'Contacts\\ContactFieldsController@destroyContactField');
        Route::get('/people/{contact}/contactfieldtypes', 'Contacts\\ContactFieldsController@getContactFieldTypes');

        // Export as vCard
        Route::get('/people/{contact}/vcard', 'ContactsController@vcard')->name('vcard');

        // Addresses
        Route::get('/countries', 'Contacts\\AddressesController@getCountries');
        Route::get('/people/{contact}/addresses', 'Contacts\\AddressesController@get');
        Route::post('/people/{contact}/addresses', 'Contacts\\AddressesController@store');
        Route::put('/people/{contact}/addresses/{address}', 'Contacts\\AddressesController@edit');
        Route::delete('/people/{contact}/addresses/{address}', 'Contacts\\AddressesController@destroy');

        // Work information
        Route::name('work.')->group(function () {
            Route::get('/people/{contact}/work/edit', 'ContactsController@editWork')->name('edit');
            Route::post('/people/{contact}/work/update', 'ContactsController@updateWork')->name('update');
        });

        // Introductions
        Route::name('introductions.')->group(function () {
            Route::get('/people/{contact}/introductions/edit', 'Contacts\\IntroductionsController@edit')->name('edit');
            Route::post('/people/{contact}/introductions/update', 'Contacts\\IntroductionsController@update')->name('update');
        });

        // Tags
        Route::name('tags.')->group(function () {
            Route::post('/people/{contact}/tags/update', 'Contacts\\TagsController@update')->name('update');
        });

        // Notes
        Route::name('notes.')->group(function () {
            Route::get('/people/{contact}/notes', 'Contacts\\NotesController@get');
            Route::post('/people/{contact}/notes', 'Contacts\\NotesController@store')->name('store');
            Route::put('/people/{contact}/notes/{note}', 'Contacts\\NotesController@update');
            Route::delete('/people/{contact}/notes/{note}', 'Contacts\\NotesController@destroy');
            Route::post('/people/{contact}/notes/{note}/toggle', 'Contacts\\NotesController@toggle');
        });

        // Food preferencies
        Route::name('food.')->group(function () {
            Route::get('/people/{contact}/food', 'ContactsController@editFoodPreferencies')->name('index');
            Route::post('/people/{contact}/food/save', 'ContactsController@updateFoodPreferencies')->name('update');
        });

        // Relationships
        Route::name('relationships.')->group(function () {
            Route::get('/people/{contact}/relationships/new', 'Contacts\\RelationshipsController@new')->name('create');
            Route::post('/people/{contact}/relationships/store', 'Contacts\\RelationshipsController@store')->name('store');
            Route::get('/people/{contact}/relationships/{otherContact}/edit', 'Contacts\\RelationshipsController@edit')->name('edit');
            Route::post('/people/{contact}/relationships/{otherContact}', 'Contacts\\RelationshipsController@update')->name('update');
            Route::delete('/people/{contact}/relationships/{otherContact}', 'Contacts\\RelationshipsController@destroy')->name('delete');
        });

        // Pets
        Route::name('pets.')->group(function () {
            Route::get('/people/{contact}/pets', 'Contacts\\PetsController@get')->name('index');
            Route::post('/people/{contact}/pet', 'Contacts\\PetsController@store')->name('store');
            Route::put('/people/{contact}/pet/{pet}', 'Contacts\\PetsController@update')->name('update');
            Route::delete('/people/{contact}/pet/{pet}', 'Contacts\\PetsController@trash')->name('delete');
            Route::get('/petcategories', 'Contacts\\PetsController@getPetCategories');
        });

        // Reminders
        Route::name('reminders.')->group(function () {
            Route::get('/people/{contact}/reminders/add', 'Contacts\\RemindersController@create')->name('add');
            Route::post('/people/{contact}/reminders/store', 'Contacts\\RemindersController@store')->name('store');
            Route::get('/people/{contact}/reminders/{reminder}/edit', 'Contacts\\RemindersController@edit')->name('edit');
            Route::put('/people/{contact}/reminders/{reminder}', 'Contacts\\RemindersController@update')->name('update');
            Route::delete('/people/{contact}/reminders/{reminder}', 'Contacts\\RemindersController@destroy')->name('delete');
        });

        // Tasks
        Route::name('tasks.')->group(function () {
            Route::get('/people/{contact}/tasks', 'Contacts\\TasksController@get')->name('index');
            Route::post('/people/{contact}/tasks', 'Contacts\\TasksController@store')->name('store');
            Route::post('/people/{contact}/tasks/{task}/toggle', 'Contacts\\TasksController@toggle');
            Route::put('/people/{contact}/tasks/{task}', 'Contacts\\TasksController@update')->name('update');
            Route::delete('/people/{contact}/tasks/{task}', 'Contacts\\TasksController@destroy')->name('delete');
        });

        // Gifts
        Route::name('gifts.')->group(function () {
            Route::get('/people/{contact}/gifts', 'Contacts\\GiftsController@get')->name('index');
            Route::post('/people/{contact}/gifts/{gift}/toggle', 'Contacts\\GiftsController@toggle');
            Route::get('/people/{contact}/gifts/add', 'Contacts\\GiftsController@create')->name('add');
            Route::get('/people/{contact}/gifts/{gift}/edit', 'Contacts\\GiftsController@edit')->name('edit');
            Route::post('/people/{contact}/gifts/store', 'Contacts\\GiftsController@store')->name('store');
            Route::post('/people/{contact}/gifts/{gift}/update', 'Contacts\\GiftsController@update')->name('update');
            Route::delete('/people/{contact}/gifts/{gift}', 'Contacts\\GiftsController@destroy')->name('delete');
        });

        // Debt
        Route::name('debt.')->group(function () {
            Route::get('/people/{contact}/debt/add', 'Contacts\\DebtController@create')->name('add');
            Route::post('/people/{contact}/debt/store', 'Contacts\\DebtController@store')->name('store');
            Route::get('/people/{contact}/debt/{debt}/edit', 'Contacts\\DebtController@edit')->name('edit');
            Route::put('/people/{contact}/debt/{debt}', 'Contacts\\DebtController@update')->name('update');
            Route::delete('/people/{contact}/debt/{debt}', 'Contacts\\DebtController@destroy')->name('delete');
        });

        // Phone calls
        Route::name('call.')->group(function () {
            Route::post('/people/{contact}/call/store', 'Contacts\\CallsController@store')->name('store');
            Route::delete('/people/{contact}/call/{call}', 'Contacts\\CallsController@destroy')->name('delete');
        });

        // Search
        Route::post('/people/search', 'ContactsController@search')->name('search');

        // Stay in touch information
        Route::post('/people/{contact}/stayintouch', 'ContactsController@stayInTouch');
    });

    // Activities
    Route::name('activities.')->group(function () {
        Route::get('/activities/add/{contact}', 'ActivitiesController@create')->name('add');
        Route::post('/activities/store/{contact}', 'ActivitiesController@store')->name('store');
        Route::get('/activities/{activity}/edit/{contact}', 'ActivitiesController@edit')->name('edit');
        Route::put('/activities/{activity}/{contact}', 'ActivitiesController@update')->name('update');
        Route::delete('/activities/{activity}/{contact}', 'ActivitiesController@destroy')->name('delete');
    });

    Route::name('journal.')->group(function () {
        Route::get('/journal', 'JournalController@index')->name('index');
        Route::get('/journal/entries', 'JournalController@list')->name('list');
        Route::get('/journal/entries/{journalEntry}', 'JournalController@get');
        Route::get('/journal/hasRated', 'JournalController@hasRated');
        Route::post('/journal/day', 'JournalController@storeDay');
        Route::delete('/journal/day/{day}', 'JournalController@trashDay');

        Route::get('/journal/add', 'JournalController@create')->name('create');
        Route::post('/journal/create', 'JournalController@save')->name('save');
        Route::delete('/journal/{entryId}', 'JournalController@deleteEntry');
    });

    Route::name('settings.')->group(function () {
        Route::get('/settings', 'SettingsController@index')->name('index');
        Route::post('/settings/delete', 'SettingsController@delete')->name('delete');
        Route::post('/settings/reset', 'SettingsController@reset')->name('reset');
        Route::post('/settings/save', 'SettingsController@save')->name('save');

        Route::name('personalization.')->group(function () {
            Route::get('/settings/personalization', 'Settings\\PersonalizationController@index')->name('index');
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
        });

        Route::get('/settings/export', 'SettingsController@export')->name('export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL')->name('sql');
        Route::get('/settings/import', 'SettingsController@import')->name('import');
        Route::get('/settings/import/report/{importjobid}', 'SettingsController@report')->name('report');
        Route::get('/settings/import/upload', 'SettingsController@upload')->name('upload');
        Route::post('/settings/import/storeImport', 'SettingsController@storeImport')->name('storeImport');

        Route::name('users.')->group(function () {
            Route::get('/settings/users', 'SettingsController@users')->name('index');
            Route::get('/settings/users/add', 'SettingsController@addUser')->name('add');
            Route::delete('/settings/users/{user}', 'SettingsController@deleteAdditionalUser')->name('delete');
            Route::post('/settings/users/save', 'SettingsController@inviteUser')->name('save');
            Route::delete('/settings/users/invitations/{invitation}', 'SettingsController@destroyInvitation')->name('invitation.delete');
        });

        Route::name('subscriptions.')->group(function () {
            Route::get('/settings/subscriptions', 'Settings\\SubscriptionsController@index')->name('index');
            Route::get('/settings/subscriptions/upgrade', 'Settings\\SubscriptionsController@upgrade')->name('upgrade');
            Route::get('/settings/subscriptions/upgrade/success', 'Settings\\SubscriptionsController@upgradeSuccess')->name('upgrade.success');
            Route::post('/settings/subscriptions/processPayment', 'Settings\\SubscriptionsController@processPayment')->name('payment');
            Route::get('/settings/subscriptions/invoice/{invoice}', 'Settings\\SubscriptionsController@downloadInvoice')->name('invoice');
            Route::get('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@downgrade')->name('downgrade');
            Route::post('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@processDowngrade');
            Route::get('/settings/subscriptions/downgrade/success', 'Settings\\SubscriptionsController@downgradeSuccess')->name('downgrade.success');
        });

        Route::name('tags.')->group(function () {
            Route::get('/settings/tags', 'SettingsController@tags')->name('index');
            Route::get('/settings/tags/add', 'SettingsController@addUser')->name('add');
            Route::delete('/settings/tags/{tag}', 'SettingsController@deleteTag')->name('delete');
        });

        Route::get('/settings/api', 'SettingsController@api')->name('api');

        // Security
        Route::name('security.')->group(function () {
            Route::get('/settings/security', 'SettingsController@security')->name('index');
            Route::post('/settings/security/passwordChange', 'Auth\\PasswordChangeController@passwordChange')->name('passwordChange');
            Route::get('/settings/security/2fa-enable', 'Settings\\MultiFAController@enableTwoFactor')->name('2fa-enable');
            Route::post('/settings/security/2fa-enable', 'Settings\\MultiFAController@validateTwoFactor');
            Route::get('/settings/security/2fa-disable', 'Settings\\MultiFAController@disableTwoFactor')->name('2fa-disable');
            Route::post('/settings/security/2fa-disable', 'Settings\\MultiFAController@deactivateTwoFactor');
            Route::get('/settings/security/u2f-register', 'Settings\\MultiFAController@u2fRegister')->name('u2f-register');
        });
    });
});
