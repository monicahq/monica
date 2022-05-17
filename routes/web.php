<?php

use App\Contact\ManageCalls\Web\Controllers\ContactModuleCallController;
use App\Contact\ManageContact\Web\Controllers\ContactController;
use App\Contact\ManageContact\Web\Controllers\ContactNoTemplateController;
use App\Contact\ManageContact\Web\Controllers\ContactPageController;
use App\Contact\ManageContact\Web\Controllers\ContactTemplateController;
use App\Contact\ManageContactImportantDates\Web\Controllers\ContactImportantDatesController;
use App\Contact\ManageJobInformation\Web\Controllers\ContactModuleJobInformationController;
use App\Contact\ManageLabels\Web\Controllers\ContactModuleLabelController;
use App\Contact\ManageLoans\Web\Controllers\ContactModuleLoanController;
use App\Contact\ManageLoans\Web\Controllers\ContactModuleToggleLoanController;
use App\Contact\ManageNotes\Web\Controllers\ContactModuleNoteController;
use App\Contact\ManageNotes\Web\Controllers\ContactNotesController;
use App\Contact\ManageRelationships\Web\Controllers\ContactRelationshipsController;
use App\Contact\ManageReminders\Web\Controllers\ContactModuleReminderController;
use App\Contact\ManageTasks\Web\Controllers\ContactModuleTaskController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Settings\CancelAccount\Web\Controllers\CancelAccountController;
use App\Settings\ManageAddressTypes\Web\Controllers\PersonalizeAddressTypeController;
use App\Settings\ManageCallReasons\Web\Controllers\PersonalizeCallReasonsController;
use App\Settings\ManageCallReasons\Web\Controllers\PersonalizeCallReasonTypesController;
use App\Settings\ManageContactInformationTypes\Web\Controllers\PersonalizeContatInformationTypesController;
use App\Settings\ManageCurrencies\Web\Controllers\CurrencyController;
use App\Settings\ManageCurrencies\Web\Controllers\PersonalizeCurrencyController;
use App\Settings\ManageGenders\Web\Controllers\ManageGenderController;
use App\Settings\ManageModules\Web\Controllers\PersonalizeModulesController;
use App\Settings\ManageNotificationChannels\Web\Controllers\NotificationsController;
use App\Settings\ManageNotificationChannels\Web\Controllers\NotificationsLogController;
use App\Settings\ManageNotificationChannels\Web\Controllers\NotificationsTestController;
use App\Settings\ManageNotificationChannels\Web\Controllers\NotificationsToggleController;
use App\Settings\ManageNotificationChannels\Web\Controllers\NotificationsVerificationController;
use App\Settings\ManagePersonalization\Web\Controllers\PersonalizeController;
use App\Settings\ManagePetCategories\Web\Controllers\PersonalizePetCategoriesController;
use App\Settings\ManagePronouns\Web\Controllers\PersonalizePronounController;
use App\Settings\ManageRelationshipTypes\Web\Controllers\PersonalizeRelationshipController;
use App\Settings\ManageRelationshipTypes\Web\Controllers\PersonalizeRelationshipTypeController;
use App\Settings\ManageSettings\Web\Controllers\SettingsController;
use App\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePageModulesController;
use App\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePageModulesPositionController;
use App\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePagePositionController;
use App\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePagesController;
use App\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatesController;
use App\Settings\ManageUserPreferences\Web\Controllers\PreferencesController;
use App\Settings\ManageUserPreferences\Web\Controllers\PreferencesDateFormatController;
use App\Settings\ManageUserPreferences\Web\Controllers\PreferencesNameOrderController;
use App\Settings\ManageUserPreferences\Web\Controllers\PreferencesNumberFormatController;
use App\Settings\ManageUserPreferences\Web\Controllers\PreferencesTimezoneController;
use App\Settings\ManageUsers\Web\Controllers\UserController;
use App\Vault\ManageVault\Web\Controllers\VaultController;
use App\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsContactImportantDateTypeController;
use App\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsController;
use App\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsLabelController;
use App\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsTemplateController;
use App\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsUserController;
use App\Vault\Search\Web\Controllers\VaultContactSearchController;
use App\Vault\Search\Web\Controllers\VaultMostConsultedContactsController;
use App\Vault\Search\Web\Controllers\VaultSearchController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
                    Route::get('no-template', [ContactNoTemplateController::class, 'show'])->name('contact.blank');
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

                    // loans
                    Route::post('loans', [ContactModuleLoanController::class, 'store'])->name('contact.loan.store');
                    Route::put('loans/{loan}', [ContactModuleLoanController::class, 'update'])->name('contact.loan.update');
                    Route::put('loans/{loan}/toggle', [ContactModuleToggleLoanController::class, 'update'])->name('contact.loan.toggle');
                    Route::delete('loans/{loan}', [ContactModuleLoanController::class, 'destroy'])->name('contact.loan.destroy');

                    // job information
                    Route::get('companies/list', [ContactModuleJobInformationController::class, 'index'])->name('contact.companies.list.index');
                    Route::put('jobInformation', [ContactModuleJobInformationController::class, 'update'])->name('contact.job_information.update');

                    // relationships
                    Route::get('relationships/create', [ContactRelationshipsController::class, 'create'])->name('contact.relationships.create');
                    Route::post('relationships', [ContactRelationshipsController::class, 'store'])->name('contact.relationships.store');
                    Route::put('relationships/{relationship}', [ContactRelationshipsController::class, 'update'])->name('contact.relationships.update');

                    // tasks
                    Route::get('tasks/completed', [ContactModuleTaskController::class, 'index'])->name('contact.task.index');
                    Route::post('tasks', [ContactModuleTaskController::class, 'store'])->name('contact.task.store');
                    Route::put('tasks/{task}', [ContactModuleTaskController::class, 'update'])->name('contact.task.update');
                    Route::put('tasks/{task}/toggle', [ContactModuleTaskController::class, 'toggle'])->name('contact.task.toggle');
                    Route::delete('tasks/{task}', [ContactModuleTaskController::class, 'destroy'])->name('contact.task.destroy');

                    // calls
                    Route::post('calls', [ContactModuleCallController::class, 'store'])->name('contact.call.store');
                    Route::put('calls/{call}', [ContactModuleCallController::class, 'update'])->name('contact.call.update');
                    Route::delete('calls/{call}', [ContactModuleCallController::class, 'destroy'])->name('contact.call.destroy');
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

                // contact important date types
                Route::post('settings/contactImportantDateTypes', [VaultSettingsContactImportantDateTypeController::class, 'store'])->name('vault.settings.important_date_type.store');
                Route::put('settings/contactImportantDateTypes/{type}', [VaultSettingsContactImportantDateTypeController::class, 'update'])->name('vault.settings.important_date_type.update');
                Route::delete('settings/contactImportantDateTypes/{type}', [VaultSettingsContactImportantDateTypeController::class, 'destroy'])->name('vault.settings.important_date_type.destroy');
            });

            // global search in the vault
            Route::get('search', [VaultSearchController::class, 'index'])->name('vault.search.index');
            Route::post('search', [VaultSearchController::class, 'show'])->name('vault.search.show');

            // contact search module
            Route::get('search/user/contact/mostConsulted', [VaultMostConsultedContactsController::class, 'index'])->name('vault.user.search.mostconsulted');
            Route::post('search/user/contacts', [VaultContactSearchController::class, 'index'])->name('vault.user.search.index');
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

                // call reason types
                Route::get('callReasonTypes', [PersonalizeCallReasonTypesController::class, 'index'])->name('call_reasons.index');
                Route::post('callReasonTypes', [PersonalizeCallReasonTypesController::class, 'store'])->name('call_reasons.type.store');
                Route::put('callReasonTypes/{callReasonType}', [PersonalizeCallReasonTypesController::class, 'update'])->name('call_reasons.type.update');
                Route::delete('callReasonTypes/{callReasonType}', [PersonalizeCallReasonTypesController::class, 'destroy'])->name('call_reasons.type.destroy');

                // call reasons
                Route::post('callReasonTypes/{callReasonType}/reasons', [PersonalizeCallReasonsController::class, 'store'])->name('call_reasons.store');
                Route::put('callReasonTypes/{callReasonType}/reasons/{reason}', [PersonalizeCallReasonsController::class, 'update'])->name('call_reasons.update');
                Route::delete('callReasonTypes/{callReasonType}/reasons/{reason}', [PersonalizeCallReasonsController::class, 'destroy'])->name('call_reasons.destroy');

                // genders
                Route::get('genders', [ManageGenderController::class, 'index'])->name('gender.index');
                Route::post('genders', [ManageGenderController::class, 'store'])->name('gender.store');
                Route::put('genders/{gender}', [ManageGenderController::class, 'update'])->name('gender.update');
                Route::delete('genders/{gender}', [ManageGenderController::class, 'destroy'])->name('gender.destroy');

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

    // General stuff called by everyone/everywhere
    Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
});
