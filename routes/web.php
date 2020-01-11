<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth as AuthFacade;

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

Route::get('/', [Auth\LoginController::class, 'showLoginOrRegister'])->name('login');

AuthFacade::routes(['verify' => true]);

Route::get('/invitations/accept/{key}', [Auth\InvitationController::class, 'show'])->name('invitations.accept');
Route::post('/invitations/accept/{key}', [Auth\InvitationController::class, 'store'])->name('invitations.send');

Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [Auth\LoginController::class, 'logout']);
    Route::get('/auth/login-recovery', [Auth\RecoveryLoginController::class, 'get'])->name('recovery.login');
    Route::post('/auth/login-recovery', [Auth\RecoveryLoginController::class, 'store']);
});

Route::middleware(['auth', '2fa'])->group(function () {
    Route::post('/validate2fa', [Auth\Validate2faController::class, 'index'])->name('validate2fa');
});

Route::middleware(['auth', 'verified', 'mfa'])->group(function () {
    Route::name('dashboard.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('index');
        Route::get('/dashboard/calls', [DashboardController::class, 'calls']);
        Route::get('/dashboard/notes', [DashboardController::class, 'notes']);
        Route::get('/dashboard/debts', [DashboardController::class, 'debts']);
        Route::post('/dashboard/setTab', [DashboardController::class, 'setTab']);
    });

    Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance');
    Route::post('/compliance/sign', [ComplianceController::class, 'store']);
    Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog.index');

    Route::get('/emotions', [EmotionController::class, 'primaries']);
    Route::get('/emotions/primaries/{emotion}/secondaries', [EmotionController::class, 'secondaries']);
    Route::get('/emotions/primaries/{emotion}/secondaries/{secondaryEmotion}/emotions', [EmotionController::class, 'emotions']);

    Route::name('people.')->group(function () {
        Route::get('/people/notfound', [ContactsController::class, 'missing'])->name('missing');
        Route::get('/people/archived', [ContactsController::class, 'archived'])->name('archived');

        // Dashboard
        Route::get('/people', [ContactsController::class, 'index'])->name('index');
        Route::get('/people/add', [ContactsController::class, 'create'])->name('create');
        Route::get('/people/list', [ContactsController::class, 'list'])->name('list');
        Route::post('/people', [ContactsController::class, 'store'])->name('store');
        Route::get('/people/{contact}', [ContactsController::class, 'show'])->name('show');
        Route::get('/people/{contact}/edit', [ContactsController::class, 'edit'])->name('edit');
        Route::put('/people/{contact}', [ContactsController::class, 'update'])->name('update');
        Route::delete('/people/{contact}', [ContactsController::class, 'destroy'])->name('destroy');

        // Avatar
        Route::get('/people/{contact}/avatar', [Contacts\AvatarController::class, 'edit'])->name('avatar.edit');
        Route::post('/people/{contact}/avatar', [Contacts\AvatarController::class, 'update'])->name('avatar.update');
        Route::post('/people/{contact}/makeProfilePicture/{photo}', [Contacts\AvatarController::class, 'photo'])->name('avatar.photo');

        // Life events
        Route::name('lifeevent.')->group(function () {
            Route::get('/people/{contact}/lifeevents', [Contacts\LifeEventsController::class, 'index'])->name('index');
            Route::get('/lifeevents/categories', [Contacts\LifeEventsController::class, 'categories'])->name('categories');
            Route::get('/lifeevents/categories/{lifeEventCategory}/types', [Contacts\LifeEventsController::class, 'types'])->name('types');
            Route::post('/people/{contact}/lifeevents', [Contacts\LifeEventsController::class, 'store'])->name('store');
            Route::delete('/lifeevents/{lifeEvent}', [Contacts\LifeEventsController::class, 'destroy'])->name('destroy');
        });

        // Contact information
        Route::get('/people/{contact}/contactfield', [Contacts\ContactFieldsController::class, 'getContactFields']);
        Route::post('/people/{contact}/contactfield', [Contacts\ContactFieldsController::class, 'storeContactField']);
        Route::put('/people/{contact}/contactfield/{contactField}', [Contacts\ContactFieldsController::class, 'editContactField']);
        Route::delete('/people/{contact}/contactfield/{contactField}', [Contacts\ContactFieldsController::class, 'destroyContactField']);
        Route::get('/people/{contact}/contactfieldtypes', [Contacts\ContactFieldsController::class, 'getContactFieldTypes']);

        // Export as vCard
        Route::get('/people/{contact}/vcard', [ContactsController::class, 'vcard'])->name('vcard');

        // Addresses
        Route::get('/countries', [Contacts\AddressesController::class, 'getCountries']);
        Route::get('/people/{contact}/addresses', [Contacts\AddressesController::class, 'index']);
        Route::post('/people/{contact}/addresses', [Contacts\AddressesController::class, 'store']);
        Route::put('/people/{contact}/addresses/{address}', [Contacts\AddressesController::class, 'edit']);
        Route::delete('/people/{contact}/addresses/{address}', [Contacts\AddressesController::class, 'destroy']);

        // Work information
        Route::name('work.')->group(function () {
            Route::get('/people/{contact}/work/edit', [ContactsController::class, 'editWork'])->name('edit');
            Route::post('/people/{contact}/work/update', [ContactsController::class, 'updateWork'])->name('update');
        });

        // Introductions
        Route::name('introductions.')->group(function () {
            Route::get('/people/{contact}/introductions/edit', [Contacts\IntroductionsController::class, 'edit'])->name('edit');
            Route::post('/people/{contact}/introductions/update', [Contacts\IntroductionsController::class, 'update'])->name('update');
        });

        // Tags
        Route::name('tags.')->group(function () {
            Route::get('/tags', [Contacts\TagsController::class, 'index'])->name('index');
            Route::get('/people/{contact}/tags', [Contacts\TagsController::class, 'get'])->name('get');
            Route::post('/people/{contact}/tags/update', [Contacts\TagsController::class, 'update'])->name('update');
        });

        // Notes
        Route::resource('people/{contact}/notes', Contacts\NotesController::class)->only([
            'index', 'store', 'update', 'destroy',
        ]);
        Route::post('/people/{contact}/notes/{note}/toggle', [Contacts\NotesController::class, 'toggle']);

        // Food preferences
        Route::name('food.')->group(function () {
            Route::get('/people/{contact}/food', [ContactsController::class, 'editFoodPreferences'])->name('index');
            Route::post('/people/{contact}/food/save', [ContactsController::class, 'updateFoodPreferences'])->name('update');
        });

        // Relationships
        Route::resource('people/{contact}/relationships', Contacts\RelationshipsController::class)->only([
            'create', 'store', 'edit', 'update', 'destroy',
        ]);

        // Pets
        Route::resource('people/{contact}/pets', Contacts\PetsController::class)->only([
            'index', 'store', 'update', 'destroy',
        ]);
        Route::get('/petcategories', [Contacts\PetsController::class, 'getPetCategories']);

        // Reminders
        Route::resource('people/{contact}/reminders', Contacts\RemindersController::class)->except(['index', 'show']);

        // Tasks
        Route::resource('people/{contact}/tasks', Contacts\TasksController::class)->only([
            'index',
        ]);
        Route::resource('tasks', TasksController::class)->only([
            'index', 'store', 'update', 'destroy',
        ]);

        // Debt
        Route::resource('people/{contact}/debts', Contacts\DebtController::class)->except(['index', 'show']);

        // Phone calls
        Route::resource('people/{contact}/calls', Contacts\CallsController::class)->except(['show']);

        // Conversations
        Route::resource('people/{contact}/conversations', Contacts\ConversationsController::class)->except(['show']);

        // Documents
        Route::resource('people/{contact}/documents', Contacts\DocumentsController::class)->only(['index', 'store', 'destroy']);

        // Photos
        Route::resource('people/{contact}/photos', Contacts\PhotosController::class)->only(['index', 'store', 'destroy']);

        // Search
        Route::post('/people/search', [ContactsController::class, 'search'])->name('search');

        // Stay in touch information
        Route::post('/people/{contact}/stayintouch', [ContactsController::class, 'stayInTouch']);

        // Set favorite
        Route::post('/people/{contact}/favorite', [ContactsController::class, 'favorite']);

        // Archive/Unarchive
        Route::put('/people/{contact}/archive', [ContactsController::class, 'archive']);

        // Activities
        Route::get('/activityCategories', [Contacts\ActivitiesController::class, 'categories'])->name('activities.categories');
        Route::resource('people/{contact}/activities', Contacts\ActivitiesController::class)->only(['index']);
        Route::get('/people/{contact}/activities/contacts', [Contacts\ActivitiesController::class, 'contacts'])->name('activities.contacts');
        Route::get('/people/{contact}/activities/summary', [Contacts\ActivitiesController::class, 'summary'])->name('activities.summary');
        Route::get('/people/{contact}/activities/{year}', [Contacts\ActivitiesController::class, 'year'])->name('activities.year');
    });

    Route::name('journal.')->group(function () {
        Route::get('/journal', [JournalController::class, 'index'])->name('index');
        Route::get('/journal/entries', [JournalController::class, 'list'])->name('list');
        Route::get('/journal/entries/{journalEntry}', [JournalController::class, 'get']);
        Route::get('/journal/hasRated', [JournalController::class, 'hasRated']);
        Route::post('/journal/day', [JournalController::class, 'storeDay']);
        Route::delete('/journal/day/{day}', [JournalController::class, 'trashDay']);

        Route::get('/journal/add', [JournalController::class, 'create'])->name('create');
        Route::post('/journal/create', [JournalController::class, 'save'])->name('save');
        Route::get('/journal/entries/{entry}/edit', [JournalController::class, 'edit'])->name('edit');
        Route::put('/journal/entries/{entry}', [JournalController::class, 'update'])->name('update');
        Route::delete('/journal/{entry}', [JournalController::class, 'deleteEntry']);
    });

    Route::name('settings.')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('index');
        Route::post('/settings/delete', [SettingsController::class, 'delete'])->name('delete');
        Route::post('/settings/reset', [SettingsController::class, 'reset'])->name('reset');
        Route::post('/settings/save', [SettingsController::class, 'save'])->name('save');

        Route::name('personalization.')->group(function () {
            Route::get('/settings/personalization', [Settings\PersonalizationController::class, 'index'])->name('index');
            Route::get('/settings/personalization/contactfieldtypes', [Settings\PersonalizationController::class, 'getContactFieldTypes']);
            Route::post('/settings/personalization/contactfieldtypes', [Settings\PersonalizationController::class, 'storeContactFieldType']);
            Route::put('/settings/personalization/contactfieldtypes/{contactFieldType}', [Settings\PersonalizationController::class, 'editContactFieldType']);
            Route::delete('/settings/personalization/contactfieldtypes/{contactFieldType}', [Settings\PersonalizationController::class, 'destroyContactFieldType']);

            Route::apiResource('settings/personalization/genders', 'Settings\GendersController');
            Route::delete('/settings/personalization/genders/{gender}/replaceby/{genderToReplaceWith}', [Settings\GendersController::class, 'destroyAndReplaceGender']);
            Route::get('/settings/personalization/genderTypes', [Settings\GendersController::class, 'types']);
            Route::put('/settings/personalization/genders/default/{gender}', [Settings\GendersController::class, 'updateDefault']);

            Route::get('/settings/personalization/reminderrules', [Settings\ReminderRulesController::class, 'index']);
            Route::post('/settings/personalization/reminderrules/{reminderRule}', [Settings\ReminderRulesController::class, 'toggle']);

            Route::get('/settings/personalization/modules', [Settings\ModulesController::class, 'index']);
            Route::post('/settings/personalization/modules/{module}', [Settings\ModulesController::class, 'toggle']);

            Route::apiResource('settings/personalization/activitytypecategories', 'Account\Activity\ActivityTypeCategoriesController');
            Route::apiResource('settings/personalization/activitytypes', 'Account\Activity\ActivityTypesController', ['except' => ['index']]);
        });

        Route::get('/settings/export', [SettingsController::class, 'export'])->name('export');
        Route::get('/settings/exportToSql', [SettingsController::class, 'exportToSQL'])->name('sql');
        Route::get('/settings/import', [SettingsController::class, 'import'])->name('import');
        Route::get('/settings/import/report/{importjobid}', [SettingsController::class, 'report'])->name('report');
        Route::get('/settings/import/upload', [SettingsController::class, 'upload'])->name('upload');
        Route::post('/settings/import/storeImport', [SettingsController::class, 'storeImport'])->name('storeImport');

        Route::name('users.')->group(function () {
            Route::get('/settings/users', [SettingsController::class, 'users'])->name('index');
            Route::get('/settings/users/create', [SettingsController::class, 'addUser'])->name('create');
            Route::post('/settings/users', [SettingsController::class, 'inviteUser'])->name('store');
            Route::delete('/settings/users/{user}', [SettingsController::class, 'deleteAdditionalUser'])->name('destroy');
            Route::delete('/settings/users/invitations/{invitation}', [SettingsController::class, 'destroyInvitation'])->name('invitation.delete');
        });

        Route::name('storage.')->group(function () {
            Route::get('/settings/storage', [Settings\StorageController::class, 'index'])->name('index');
        });

        Route::name('subscriptions.')->group(function () {
            Route::get('/settings/subscriptions', [Settings\SubscriptionsController::class, 'index'])->name('index');
            Route::get('/settings/subscriptions/upgrade', [Settings\SubscriptionsController::class, 'upgrade'])->name('upgrade');
            Route::get('/settings/subscriptions/upgrade/success', [Settings\SubscriptionsController::class, 'upgradeSuccess'])->name('upgrade.success');
            Route::get('/settings/subscriptions/confirmPayment/{id}', [Settings\SubscriptionsController::class, 'confirmPayment'])->name('confirm');
            Route::post('/settings/subscriptions/processPayment', [Settings\SubscriptionsController::class, 'processPayment'])->name('payment');
            Route::get('/settings/subscriptions/invoice/{invoice}', [Settings\SubscriptionsController::class, 'downloadInvoice'])->name('invoice');
            Route::get('/settings/subscriptions/downgrade', [Settings\SubscriptionsController::class, 'downgrade'])->name('downgrade');
            Route::post('/settings/subscriptions/downgrade', [Settings\SubscriptionsController::class, 'processDowngrade']);
            Route::get('/settings/subscriptions/downgrade/success', [Settings\SubscriptionsController::class, 'downgradeSuccess'])->name('downgrade.success');
            if (! App::environment('production')) {
                Route::get('/settings/subscriptions/forceCompletePaymentOnTesting', [Settings\SubscriptionsController::class, 'forceCompletePaymentOnTesting'])->name('forceCompletePaymentOnTesting');
            }
        });

        Route::name('tags.')->group(function () {
            Route::get('/settings/tags', [SettingsController::class, 'tags'])->name('index');
            Route::get('/settings/tags/add', [SettingsController::class, 'addUser'])->name('add');
            Route::delete('/settings/tags/{tag}', [SettingsController::class, 'deleteTag'])->name('delete');
        });

        Route::get('/settings/api', [SettingsController::class, 'api'])->name('api');
        Route::get('/settings/dav', [SettingsController::class, 'dav'])->name('dav');

        Route::post('/settings/updateDefaultProfileView', [SettingsController::class, 'updateDefaultProfileView']);

        // Security
        Route::name('security.')->group(function () {
            Route::get('/settings/security', [SettingsController::class, 'security'])->name('index');
            Route::post('/settings/security/passwordChange', [Auth\PasswordChangeController::class, 'passwordChange'])->name('passwordChange');
            Route::get('/settings/security/2fa-enable', [Settings\MultiFAController::class, 'enableTwoFactor'])->name('2fa-enable');
            Route::post('/settings/security/2fa-enable', [Settings\MultiFAController::class, 'validateTwoFactor']);
            Route::get('/settings/security/2fa-disable', [Settings\MultiFAController::class, 'disableTwoFactor'])->name('2fa-disable');
            Route::post('/settings/security/2fa-disable', [Settings\MultiFAController::class, 'deactivateTwoFactor']);
            Route::get('/settings/security/u2f/register', [Settings\MultiFAController::class, 'u2fRegisterData']);
            Route::post('/settings/security/u2f/register', [Settings\MultiFAController::class, 'u2fRegister']);
            Route::delete('/settings/security/u2f/remove/{u2fKeyId}', [Settings\MultiFAController::class, 'u2fRemove']);

            Route::post('/settings/security/generate-recovery-codes', [Settings\RecoveryCodesController::class, 'store']);
            Route::post('/settings/security/recovery-codes', [Settings\RecoveryCodesController::class, 'index']);
        });
    });
});
