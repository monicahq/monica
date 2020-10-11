<?php

use Illuminate\Support\Facades\App;
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

Route::get('/', 'Auth\LoginController@showLoginOrRegister')->name('loginRedirect');

Auth::routes(['verify' => true]);

Route::get('/invitations/accept/{key}', 'Auth\InvitationController@show')->name('invitations.accept');
Route::post('/invitations/accept/{key}', 'Auth\InvitationController@store')->name('invitations.send');

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', 'Auth\LoginController@logout');
    Route::get('/auth/login-recovery', 'Auth\RecoveryLoginController@get')->name('recovery.login');
    Route::post('/auth/login-recovery', 'Auth\RecoveryLoginController@store');
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::post('/validate2fa', 'Auth\Validate2faController@index')->name('validate2fa');
});

Route::middleware(['auth', 'verified', 'mfa'])->group(function () {
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

    Route::get('/emotions', 'EmotionController@primaries');
    Route::get('/emotions/primaries/{emotion}/secondaries', 'EmotionController@secondaries');
    Route::get('/emotions/primaries/{emotion}/secondaries/{secondaryEmotion}/emotions', 'EmotionController@emotions');

    Route::name('people.')->group(function () {
        Route::get('/people/notfound', 'ContactsController@missing')->name('missing');
        Route::get('/people/archived', 'ContactsController@archived')->name('archived');

        // Dashboard
        Route::get('/people', 'ContactsController@index')->name('index');
        Route::get('/people/add', 'ContactsController@create')->name('create');
        Route::get('/people/list', 'ContactsController@list')->name('list');
        Route::post('/people', 'ContactsController@store')->name('store');
        Route::get('/people/{contact}', 'ContactsController@show')->name('show');
        Route::get('/people/{contact}/edit', 'ContactsController@edit')->name('edit');
        Route::put('/people/{contact}', 'ContactsController@update')->name('update');
        Route::delete('/people/{contact}', 'ContactsController@destroy')->name('destroy');

        // Avatar
        Route::get('/people/{contact}/avatar', 'Contacts\\AvatarController@edit')->name('avatar.edit');
        Route::post('/people/{contact}/avatar', 'Contacts\\AvatarController@update')->name('avatar.update');
        Route::post('/people/{contact}/makeProfilePicture/{photo}', 'Contacts\\AvatarController@photo')->name('avatar.photo');

        // Life events
        Route::name('lifeevent.')->group(function () {
            Route::get('/people/{contact}/lifeevents', 'Contacts\\LifeEventsController@index')->name('index');
            Route::get('/lifeevents/categories', 'Contacts\\LifeEventsController@categories')->name('categories');
            Route::get('/lifeevents/categories/{lifeEventCategory}/types', 'Contacts\\LifeEventsController@types')->name('types');
            Route::post('/people/{contact}/lifeevents', 'Contacts\\LifeEventsController@store')->name('store');
            Route::delete('/lifeevents/{lifeEvent}', 'Contacts\\LifeEventsController@destroy')->name('destroy');
        });

        // Contact information
        Route::get('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@getContactFields');
        Route::post('/people/{contact}/contactfield', 'Contacts\\ContactFieldsController@storeContactField');
        Route::put('/people/{contact}/contactfield/{contactField}', 'Contacts\\ContactFieldsController@editContactField');
        Route::delete('/people/{contact}/contactfield/{contactField}', 'Contacts\\ContactFieldsController@destroyContactField');
        Route::get('/people/{contact}/contactfieldtypes', 'Contacts\\ContactFieldsController@getContactFieldTypes');

        // Export as vCard
        Route::get('/people/{contact}/vcard', 'ContactsController@vcard')->name('vcard');

        // Addresses
        Route::get('/countries', 'Contacts\\AddressesController@getCountries');
        Route::get('/people/{contact}/addresses', 'Contacts\\AddressesController@index');
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
            Route::get('/tags', 'Contacts\\TagsController@index')->name('index');
            Route::get('/people/{contact}/tags', 'Contacts\\TagsController@get')->name('get');
            Route::post('/people/{contact}/tags/update', 'Contacts\\TagsController@update')->name('update');
        });

        // Notes
        Route::resource('people/{contact}/notes', 'Contacts\\NotesController')->only([
            'index', 'store', 'update', 'destroy',
        ]);
        Route::post('/people/{contact}/notes/{note}/toggle', 'Contacts\\NotesController@toggle');

        // Food preferences
        Route::name('food.')->group(function () {
            Route::get('/people/{contact}/food', 'ContactsController@editFoodPreferences')->name('index');
            Route::post('/people/{contact}/food/save', 'ContactsController@updateFoodPreferences')->name('update');
        });

        // Relationships
        Route::resource('people/{contact}/relationships', 'Contacts\\RelationshipsController')->only([
            'create', 'store', 'edit', 'update', 'destroy',
        ]);

        // Pets
        Route::resource('people/{contact}/pets', 'Contacts\\PetsController')->only([
            'index', 'store', 'update', 'destroy',
        ]);
        Route::get('/petcategories', 'Contacts\\PetsController@getPetCategories');

        // Reminders
        Route::resource('people/{contact}/reminders', 'Contacts\\RemindersController')->except(['index', 'show']);

        // Tasks
        Route::get('people/{contact}/tasks', 'Contacts\\TasksController@index')->name('tasks.get');
        Route::resource('tasks', 'TasksController')->only([
            'index', 'store', 'update', 'destroy',
        ]);

        // Debt
        Route::resource('people/{contact}/debts', 'Contacts\\DebtController')->except(['index', 'show']);

        // Phone calls
        Route::resource('people/{contact}/calls', 'Contacts\\CallsController')->except(['show']);

        // Conversations
        Route::resource('people/{contact}/conversations', 'Contacts\\ConversationsController')->except(['show']);

        // Documents
        Route::resource('people/{contact}/documents', 'Contacts\\DocumentsController')->only(['index', 'store', 'destroy']);

        // Photos
        Route::resource('people/{contact}/photos', 'Contacts\\PhotosController')->only(['index', 'store', 'destroy']);

        // Search
        Route::post('/people/search', 'ContactsController@search')->name('search');

        // Stay in touch information
        Route::post('/people/{contact}/stayintouch', 'ContactsController@stayInTouch');

        // Set favorite
        Route::post('/people/{contact}/favorite', 'ContactsController@favorite');

        // Archive/Unarchive
        Route::put('/people/{contact}/archive', 'ContactsController@archive');

        // Activities
        Route::get('/activityCategories', 'Contacts\\ActivitiesController@categories')->name('activities.categories');
        Route::resource('people/{contact}/activities', 'Contacts\\ActivitiesController')->only(['index']);
        Route::get('/people/{contact}/activities/contacts', 'Contacts\\ActivitiesController@contacts')->name('activities.contacts');
        Route::get('/people/{contact}/activities/summary', 'Contacts\\ActivitiesController@summary')->name('activities.summary');
        Route::get('/people/{contact}/activities/{year}', 'Contacts\\ActivitiesController@year')->name('activities.year');

        // Audit logs
        Route::get('/people/{contact}/auditlogs', 'Contacts\\ContactAuditLogController@index')->name('auditlogs');
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
        Route::get('/journal/entries/{entry}/edit', 'JournalController@edit')->name('edit');
        Route::put('/journal/entries/{entry}', 'JournalController@update')->name('update');
        Route::delete('/journal/{entry}', 'JournalController@deleteEntry');
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
            Route::put('/settings/personalization/contactfieldtypes/{contactFieldType}', 'Settings\\PersonalizationController@editContactFieldType');
            Route::delete('/settings/personalization/contactfieldtypes/{contactFieldType}', 'Settings\\PersonalizationController@destroyContactFieldType');

            Route::apiResource('settings/personalization/genders', 'Settings\\GendersController');
            Route::delete('/settings/personalization/genders/{gender}/replaceby/{genderToReplaceWith}', 'Settings\\GendersController@destroyAndReplaceGender');
            Route::get('/settings/personalization/genderTypes', 'Settings\\GendersController@types');
            Route::put('/settings/personalization/genders/default/{gender}', 'Settings\\GendersController@updateDefault');

            Route::get('/settings/personalization/reminderrules', 'Settings\\ReminderRulesController@index');
            Route::post('/settings/personalization/reminderrules/{reminderRule}', 'Settings\\ReminderRulesController@toggle');

            Route::get('/settings/personalization/modules', 'Settings\\ModulesController@index');
            Route::post('/settings/personalization/modules/{module}', 'Settings\\ModulesController@toggle');

            Route::apiResource('settings/personalization/activitytypecategories', 'Account\\Activity\\ActivityTypeCategoriesController');
            Route::apiResource('settings/personalization/activitytypes', 'Account\\Activity\\ActivityTypesController', ['except' => ['index']]);
        });

        Route::get('/settings/export', 'SettingsController@export')->name('export');
        Route::get('/settings/exportToSql', 'SettingsController@exportToSQL')->name('sql');
        Route::get('/settings/import', 'SettingsController@import')->name('import');
        Route::get('/settings/import/report/{importjobid}', 'SettingsController@report')->name('report');
        Route::get('/settings/import/upload', 'SettingsController@upload')->name('upload');
        Route::post('/settings/import/storeImport', 'SettingsController@storeImport')->name('storeImport');

        Route::name('users.')->group(function () {
            Route::get('/settings/users', 'SettingsController@users')->name('index');
            Route::get('/settings/users/create', 'SettingsController@addUser')->name('create');
            Route::post('/settings/users', 'SettingsController@inviteUser')->name('store');
            Route::delete('/settings/users/{user}', 'SettingsController@deleteAdditionalUser')->name('destroy');
            Route::delete('/settings/users/invitations/{invitation}', 'SettingsController@destroyInvitation')->name('invitation.delete');
        });

        Route::name('storage.')->group(function () {
            Route::get('/settings/storage', 'Settings\\StorageController@index')->name('index');
        });

        Route::name('subscriptions.')->group(function () {
            Route::get('/settings/subscriptions', 'Settings\\SubscriptionsController@index')->name('index');
            Route::get('/settings/subscriptions/upgrade', 'Settings\\SubscriptionsController@upgrade')->name('upgrade');
            Route::get('/settings/subscriptions/upgrade/success', 'Settings\\SubscriptionsController@upgradeSuccess')->name('upgrade.success');
            Route::get('/settings/subscriptions/confirmPayment/{id}', 'Settings\\SubscriptionsController@confirmPayment')->name('confirm');
            Route::post('/settings/subscriptions/processPayment', 'Settings\\SubscriptionsController@processPayment')->name('payment');
            Route::get('/settings/subscriptions/invoice/{invoice}', 'Settings\\SubscriptionsController@downloadInvoice')->name('invoice');
            Route::get('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@downgrade')->name('downgrade');
            Route::post('/settings/subscriptions/downgrade', 'Settings\\SubscriptionsController@processDowngrade');
            Route::get('/settings/subscriptions/archive', 'Settings\\SubscriptionsController@archive')->name('archive');
            Route::post('/settings/subscriptions/archive', 'Settings\\SubscriptionsController@processArchive');
            Route::get('/settings/subscriptions/downgrade/success', 'Settings\\SubscriptionsController@downgradeSuccess')->name('downgrade.success');
            if (! App::environment('production')) {
                Route::get('/settings/subscriptions/forceCompletePaymentOnTesting', 'Settings\\SubscriptionsController@forceCompletePaymentOnTesting')->name('forceCompletePaymentOnTesting');
            }
        });

        Route::get('/settings/auditlogs', 'Settings\\AuditLogController@index')->name('auditlog.index');

        Route::name('tags.')->group(function () {
            Route::get('/settings/tags', 'SettingsController@tags')->name('index');
            Route::get('/settings/tags/add', 'SettingsController@addUser')->name('add');
            Route::delete('/settings/tags/{tag}', 'SettingsController@deleteTag')->name('delete');
        });

        Route::get('/settings/api', 'SettingsController@api')->name('api');
        Route::get('/settings/dav', 'SettingsController@dav')->name('dav');

        Route::post('/settings/updateDefaultProfileView', 'SettingsController@updateDefaultProfileView');

        // Security
        Route::name('security.')->group(function () {
            Route::get('/settings/security', 'SettingsController@security')->name('index');
            Route::post('/settings/security/passwordChange', 'Auth\\PasswordChangeController@passwordChange')->name('passwordChange');
            Route::get('/settings/security/2fa-enable', 'Settings\\MultiFAController@enableTwoFactor')->name('2fa-enable');
            Route::post('/settings/security/2fa-enable', 'Settings\\MultiFAController@validateTwoFactor');
            Route::get('/settings/security/2fa-disable', 'Settings\\MultiFAController@disableTwoFactor')->name('2fa-disable');
            Route::post('/settings/security/2fa-disable', 'Settings\\MultiFAController@deactivateTwoFactor');

            Route::post('/settings/security/generate-recovery-codes', 'Settings\\RecoveryCodesController@store');
            Route::post('/settings/security/recovery-codes', 'Settings\\RecoveryCodesController@index');
        });
    });
});
