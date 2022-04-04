<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Vault\VaultController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\Users\UserController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Vault\Contact\ContactController;
use App\Http\Controllers\Vault\Search\VaultSearchController;
use App\Http\Controllers\Vault\Contact\ContactPageController;
use App\Http\Controllers\Vault\Settings\VaultSettingsController;
use App\Http\Controllers\Vault\Contact\ContactTemplateController;
use App\Http\Controllers\Settings\Personalize\PersonalizeController;
use App\Http\Controllers\Settings\Preferences\PreferencesController;
use App\Http\Controllers\Vault\Contact\Notes\ContactNotesController;
use App\Http\Controllers\Vault\Settings\VaultSettingsUserController;
use App\Http\Controllers\Vault\Settings\VaultSettingsLabelController;
use App\Http\Controllers\Settings\CancelAccount\CancelAccountController;
use App\Http\Controllers\Settings\Notifications\NotificationsController;
use App\Http\Controllers\Vault\Settings\VaultSettingsTemplateController;
use App\Http\Controllers\Settings\Notifications\NotificationsLogController;
use App\Http\Controllers\Settings\Notifications\NotificationsTestController;
use App\Http\Controllers\Settings\Preferences\PreferencesTimezoneController;
use App\Http\Controllers\Settings\Preferences\PreferencesNameOrderController;
use App\Http\Controllers\Settings\Notifications\NotificationsToggleController;
use App\Http\Controllers\Settings\Preferences\PreferencesDateFormatController;
use App\Http\Controllers\Settings\Preferences\PreferencesNumberFormatController;
use App\Http\Controllers\Vault\Contact\Modules\Note\ContactModuleNoteController;
use App\Http\Controllers\Settings\Personalize\Genders\PersonalizeGenderController;
use App\Http\Controllers\Vault\Contact\Modules\Label\ContactModuleLabelController;
use App\Http\Controllers\Settings\Personalize\Modules\PersonalizeModulesController;
use App\Http\Controllers\Settings\Notifications\NotificationsVerificationController;
use App\Http\Controllers\Settings\Personalize\Pronouns\PersonalizePronounController;
use App\Http\Controllers\Vault\Contact\ImportantDates\ContactImportantDatesController;
use App\Http\Controllers\Settings\Personalize\Currencies\PersonalizeCurrencyController;
use App\Http\Controllers\Settings\Personalize\Templates\PersonalizeTemplatesController;
use App\Http\Controllers\Vault\Contact\Modules\Reminder\ContactModuleReminderController;
use App\Http\Controllers\Settings\Personalize\Templates\PersonalizeTemplatePagesController;
use App\Http\Controllers\Settings\Personalize\AddressTypes\PersonalizeAddressTypeController;
use App\Http\Controllers\Settings\Personalize\Relationships\PersonalizeRelationshipController;
use App\Http\Controllers\Settings\Personalize\PetCategories\PersonalizePetCategoriesController;
use App\Http\Controllers\Settings\Personalize\Templates\PersonalizeTemplatePageModulesController;
use App\Http\Controllers\Settings\Personalize\Relationships\PersonalizeRelationshipTypeController;
use App\Http\Controllers\Settings\Personalize\Templates\PersonalizeTemplatePagePositionController;
use App\Http\Controllers\Settings\Personalize\Templates\PersonalizeTemplatePageModulesPositionController;
use App\Http\Controllers\Settings\Personalize\ContactInformationTypes\PersonalizeContatInformationTypesController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

require __DIR__.'/auth.php';

Route::get('invitation/{code}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
Route::post('invitation', [AcceptInvitationController::class, 'store'])->name('invitation.store');

Route::middleware(['auth', 'verified'])->group(function () {
    // vaults
    Route::prefix('vaults')->group(function () {
        Route::get('', [VaultController::class, 'index'])->name('vault.index');
        Route::get('create', [VaultController::class, 'create'])->name('vault.create');
        Route::post('', [VaultController::class, 'store'])->name('vault.store');

        Route::middleware(['vault'])->prefix('{vault}')->group(function () {
            Route::get('', [VaultController::class, 'show'])->name('vault.show');

            // vault contacts
            Route::prefix('contacts')->group(function () {
                Route::get('', [ContactController::class, 'index'])->name('contact.index');

                // create a contact
                Route::middleware(['atLeastVaultEditor'])->get('create', [ContactController::class, 'create'])->name('contact.create');
                Route::middleware(['atLeastVaultEditor'])->post('', [ContactController::class, 'store'])->name('contact.store');

                // contact page
                Route::middleware(['contact'])->prefix('{contact}')->group(function () {
                    Route::get('', [ContactController::class, 'show'])->name('contact.show');
                    Route::get('/edit', [ContactController::class, 'edit'])->name('contact.edit');
                    Route::post('', [ContactController::class, 'update'])->name('contact.update');
                    Route::delete('', [ContactController::class, 'destroy'])->name('contact.destroy');
                    Route::get('no-template', [ContactController::class, 'blank'])->name('contact.blank');
                    Route::put('template', [ContactTemplateController::class, 'update'])->name('contact.template.update');

                    Route::get('tabs/{slug}', [ContactPageController::class, 'show'])->name('contact.page.show');

                    // important dates
                    Route::get('dates', [ContactImportantDatesController::class, 'index'])->name('contact.date.index');
                    Route::post('dates', [ContactImportantDatesController::class, 'store'])->name('contact.date.store');
                    Route::put('dates/{date}', [ContactImportantDatesController::class, 'update'])->name('contact.date.update');
                    Route::delete('dates/{date}', [ContactImportantDatesController::class, 'destroy'])->name('contact.date.destroy');

                    // notes
                    Route::get('notes', [ContactNotesController::class, 'index'])->name('contact.note.index');
                    Route::post('notes', [ContactModuleNoteController::class, 'store'])->name('contact.note.store');
                    Route::put('notes/{note}', [ContactModuleNoteController::class, 'update'])->name('contact.note.update');
                    Route::delete('notes/{note}', [ContactModuleNoteController::class, 'destroy'])->name('contact.note.destroy');

                    // labels
                    Route::post('labels', [ContactModuleLabelController::class, 'store'])->name('contact.label.store');
                    Route::put('labels/{label}', [ContactModuleLabelController::class, 'update'])->name('contact.label.update');
                    Route::delete('labels/{label}', [ContactModuleLabelController::class, 'destroy'])->name('contact.label.destroy');

                    // reminders
                    Route::post('reminders', [ContactModuleReminderController::class, 'store'])->name('contact.reminder.store');
                    Route::put('reminders/{reminder}', [ContactModuleReminderController::class, 'update'])->name('contact.reminder.update');
                    Route::delete('reminders/{reminder}', [ContactModuleReminderController::class, 'destroy'])->name('contact.reminder.destroy');
                });
            });

            // vault settings
            Route::middleware(['atLeastVaultManager'])->group(function () {
                Route::get('settings', [VaultSettingsController::class, 'index'])->name('vault.settings.index');
                Route::put('settings', [VaultSettingsController::class, 'update'])->name('vault.settings.update');
                Route::put('settings/template', [VaultSettingsTemplateController::class, 'update'])->name('vault.settings.template.update');
                Route::post('settings/users', [VaultSettingsUserController::class, 'store'])->name('vault.settings.user.store');
                Route::put('settings/users/{user}', [VaultSettingsUserController::class, 'update'])->name('vault.settings.user.update');
                Route::delete('settings/users/{user}', [VaultSettingsUserController::class, 'destroy'])->name('vault.settings.user.destroy');
                Route::delete('', [VaultController::class, 'destroy'])->name('vault.settings.destroy');

                // labels
                Route::get('settings/labels', [VaultSettingsLabelController::class, 'index'])->name('vault.settings.label.index');
                Route::post('settings/labels', [VaultSettingsLabelController::class, 'store'])->name('vault.settings.label.store');
                Route::put('settings/labels/{label}', [VaultSettingsLabelController::class, 'update'])->name('vault.settings.label.update');
                Route::delete('settings/labels/{label}', [VaultSettingsLabelController::class, 'destroy'])->name('vault.settings.label.destroy');
            });

            // search
            Route::get('search', [VaultSearchController::class, 'index'])->name('vault.search.index');
            Route::post('search', [VaultSearchController::class, 'show'])->name('vault.search.show');
        });
    });

    // settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('', [SettingsController::class, 'index'])->name('index');

        // preferences
        Route::prefix('preferences')->name('preferences.')->group(function () {
            Route::get('', [PreferencesController::class, 'index'])->name('index');
            Route::post('name', [PreferencesNameOrderController::class, 'store'])->name('name.store');
            Route::post('date', [PreferencesDateFormatController::class, 'store'])->name('date.store');
            Route::post('timezone', [PreferencesTimezoneController::class, 'store'])->name('timezone.store');
            Route::post('number', [PreferencesNumberFormatController::class, 'store'])->name('number.store');
        });

        // notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('', [NotificationsController::class, 'index'])->name('index');
            Route::post('', [NotificationsController::class, 'store'])->name('store');
            Route::get('{notification}/verify/{uuid}', [NotificationsVerificationController::class, 'store'])->name('verification.store');
            Route::post('{notification}/test', [NotificationsTestController::class, 'store'])->name('test.store');
            Route::put('{notification}/toggle', [NotificationsToggleController::class, 'update'])->name('toggle.update');
            Route::delete('{notification}', [NotificationsController::class, 'destroy'])->name('destroy');

            // notification logs
            Route::get('{notification}/logs', [NotificationsLogController::class, 'index'])->name('log.index');
        });

        // only for administrators
        Route::middleware(['administrator'])->group(function () {
            // users
            Route::prefix('users')->name('user.')->group(function () {
                Route::get('', [UserController::class, 'index'])->name('index');
                Route::get('create', [UserController::class, 'create'])->name('create');
                Route::post('', [UserController::class, 'store'])->name('store');
                Route::get('{user}', [UserController::class, 'show'])->name('show');
                Route::put('{user}', [UserController::class, 'update'])->name('update');
                Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
            });

            // personalize
            Route::prefix('personalize')->name('personalize.')->group(function () {
                Route::get('', [PersonalizeController::class, 'index'])->name('index');

                // relationship group types
                Route::get('relationships', [PersonalizeRelationshipController::class, 'index'])->name('relationship.index');
                Route::post('relationships', [PersonalizeRelationshipController::class, 'store'])->name('relationship.grouptype.store');
                Route::put('relationships/{groupType}', [PersonalizeRelationshipController::class, 'update'])->name('relationship.grouptype.update');
                Route::delete('relationships/{groupType}', [PersonalizeRelationshipController::class, 'destroy'])->name('relationship.grouptype.destroy');

                // relationship group types
                Route::post('relationships/{groupType}/types', [PersonalizeRelationshipTypeController::class, 'store'])->name('relationship.type.store');
                Route::put('relationships/{groupType}/types/{type}', [PersonalizeRelationshipTypeController::class, 'update'])->name('relationship.type.update');
                Route::delete('relationships/{groupType}/types/{type}', [PersonalizeRelationshipTypeController::class, 'destroy'])->name('relationship.type.destroy');

                // genders
                Route::get('genders', [PersonalizeGenderController::class, 'index'])->name('gender.index');
                Route::post('genders', [PersonalizeGenderController::class, 'store'])->name('gender.store');
                Route::put('genders/{gender}', [PersonalizeGenderController::class, 'update'])->name('gender.update');
                Route::delete('genders/{gender}', [PersonalizeGenderController::class, 'destroy'])->name('gender.destroy');

                // pronouns
                Route::get('pronouns', [PersonalizePronounController::class, 'index'])->name('pronoun.index');
                Route::post('pronouns', [PersonalizePronounController::class, 'store'])->name('pronoun.store');
                Route::put('pronouns/{pronoun}', [PersonalizePronounController::class, 'update'])->name('pronoun.update');
                Route::delete('pronouns/{pronoun}', [PersonalizePronounController::class, 'destroy'])->name('pronoun.destroy');

                // address types
                Route::get('addressTypes', [PersonalizeAddressTypeController::class, 'index'])->name('address_type.index');
                Route::post('addressTypes', [PersonalizeAddressTypeController::class, 'store'])->name('address_type.store');
                Route::put('addressTypes/{addressType}', [PersonalizeAddressTypeController::class, 'update'])->name('address_type.update');
                Route::delete('addressTypes/{addressType}', [PersonalizeAddressTypeController::class, 'destroy'])->name('address_type.destroy');

                // pet categories
                Route::get('petCategories', [PersonalizePetCategoriesController::class, 'index'])->name('pet_category.index');
                Route::post('petCategories', [PersonalizePetCategoriesController::class, 'store'])->name('pet_category.store');
                Route::put('petCategories/{petCategory}', [PersonalizePetCategoriesController::class, 'update'])->name('pet_category.update');
                Route::delete('petCategories/{petCategory}', [PersonalizePetCategoriesController::class, 'destroy'])->name('pet_category.destroy');

                // contact information
                Route::get('contactInformationType', [PersonalizeContatInformationTypesController::class, 'index'])->name('contact_information_type.index');
                Route::post('contactInformationType', [PersonalizeContatInformationTypesController::class, 'store'])->name('contact_information_type.store');
                Route::put('contactInformationType/{type}', [PersonalizeContatInformationTypesController::class, 'update'])->name('contact_information_type.update');
                Route::delete('contactInformationType/{type}', [PersonalizeContatInformationTypesController::class, 'destroy'])->name('contact_information_type.destroy');

                // templates
                Route::prefix('templates')->name('template.')->group(function () {
                    Route::get('', [PersonalizeTemplatesController::class, 'index'])->name('index');
                    Route::post('', [PersonalizeTemplatesController::class, 'store'])->name('store');
                    Route::put('{template}', [PersonalizeTemplatesController::class, 'update'])->name('update');
                    Route::delete('{template}', [PersonalizeTemplatesController::class, 'destroy'])->name('destroy');
                    Route::get('{template}', [PersonalizeTemplatesController::class, 'show'])->name('show');

                    // template pages
                    Route::post('{template}', [PersonalizeTemplatePagesController::class, 'store'])->name('template_page.store');
                    Route::put('{template}/template_pages/{page}', [PersonalizeTemplatePagesController::class, 'update'])->name('template_page.update');
                    Route::delete('{template}/template_pages/{page}', [PersonalizeTemplatePagesController::class, 'destroy'])->name('template_page.destroy');
                    Route::get('{template}/template_pages/{page}', [PersonalizeTemplatePagesController::class, 'show'])->name('template_page.show');
                    Route::post('{template}/template_pages/{page}/order', [PersonalizeTemplatePagePositionController::class, 'update'])->name('template_page.order.update');

                    // modules in template page
                    Route::post('{template}/template_pages/{page}/modules', [PersonalizeTemplatePageModulesController::class, 'store'])->name('template_page.module.store');
                    Route::post('{template}/template_pages/{page}/modules/{module}/order', [PersonalizeTemplatePageModulesPositionController::class, 'update'])->name('template_page.module.order.update');
                    Route::delete('{template}/template_pages/{page}/modules/{module}', [PersonalizeTemplatePageModulesController::class, 'destroy'])->name('template_page.module.destroy');
                });

                // modules
                Route::get('modules', [PersonalizeModulesController::class, 'index'])->name('module.index');
                Route::post('modules', [PersonalizeModulesController::class, 'store'])->name('module.store');
                Route::put('modules/{module}', [PersonalizeModulesController::class, 'update'])->name('module.update');
                Route::delete('modules/{module}', [PersonalizeModulesController::class, 'destroy'])->name('module.destroy');

                // currencies
                Route::get('currencies', [PersonalizeCurrencyController::class, 'index'])->name('currency.index');
                Route::put('currencies/{currency}', [PersonalizeCurrencyController::class, 'update'])->name('currency.update');
                Route::post('currencies', [PersonalizeCurrencyController::class, 'store'])->name('currency.store');
                Route::delete('currencies', [PersonalizeCurrencyController::class, 'destroy'])->name('currency.destroy');
            });

            // cancel
            Route::get('cancel', [CancelAccountController::class, 'index'])->name('cancel.index');
            Route::put('cancel', [CancelAccountController::class, 'destroy'])->name('cancel.destroy');
        });
    });

    Route::resource('settings/information', 'Settings\\InformationController');
});
