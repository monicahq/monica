<?php

use App\Domains\Contact\ManageAvatar\Web\Controllers\ModuleAvatarController;
use App\Domains\Contact\ManageCalls\Web\Controllers\ContactModuleCallController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactArchiveController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactFavoriteController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactLabelController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactMoveController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactNoTemplateController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactPageController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactSortController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactTemplateController;
use App\Domains\Contact\ManageContact\Web\Controllers\ContactVCardController;
use App\Domains\Contact\ManageContactAddresses\Web\Controllers\ContactModuleAddressController;
use App\Domains\Contact\ManageContactAddresses\Web\Controllers\ContactModuleAddressImageController;
use App\Domains\Contact\ManageContactFeed\Web\Controllers\ContactFeedController;
use App\Domains\Contact\ManageContactImportantDates\Web\Controllers\ContactImportantDatesController;
use App\Domains\Contact\ManageContactInformation\Web\Controllers\ContactInformationController;
use App\Domains\Contact\ManageDocuments\Web\Controllers\ContactModuleDocumentController;
use App\Domains\Contact\ManageGoals\Web\Controllers\ContactGoalController;
use App\Domains\Contact\ManageGoals\Web\Controllers\ContactModuleGoalController;
use App\Domains\Contact\ManageGoals\Web\Controllers\ContactModuleStreakController;
use App\Domains\Contact\ManageGroups\Web\Controllers\ContactModuleGroupController;
use App\Domains\Contact\ManageGroups\Web\Controllers\GroupController;
use App\Domains\Contact\ManageJobInformation\Web\Controllers\ContactModuleJobInformationController;
use App\Domains\Contact\ManageLabels\Web\Controllers\ContactModuleLabelController;
use App\Domains\Contact\ManageLifeEvents\Web\Controllers\ContactModuleLifeEventController;
use App\Domains\Contact\ManageLifeEvents\Web\Controllers\ContactModuleTimelineEventController;
use App\Domains\Contact\ManageLifeEvents\Web\Controllers\ToggleLifeEventController;
use App\Domains\Contact\ManageLifeEvents\Web\Controllers\ToggleTimelineEventController;
use App\Domains\Contact\ManageLoans\Web\Controllers\ContactModuleLoanController;
use App\Domains\Contact\ManageLoans\Web\Controllers\ContactModuleToggleLoanController;
use App\Domains\Contact\ManageMoodTrackingEvents\Web\Controllers\ContactMoodTrackingEventsController;
use App\Domains\Contact\ManageNotes\Web\Controllers\ContactModuleNoteController;
use App\Domains\Contact\ManageNotes\Web\Controllers\ContactNotesController;
use App\Domains\Contact\ManagePets\Web\Controllers\ContactModulePetController;
use App\Domains\Contact\ManagePhotos\Web\Controllers\ContactModulePhotoController;
use App\Domains\Contact\ManagePhotos\Web\Controllers\ContactPhotoController;
use App\Domains\Contact\ManageQuickFacts\Web\Controllers\ContactQuickFactController;
use App\Domains\Contact\ManageQuickFacts\Web\Controllers\ContactQuickFactToggleController;
use App\Domains\Contact\ManageRelationships\Web\Controllers\ContactRelationshipsController;
use App\Domains\Contact\ManageReligion\Web\Controllers\ContactModuleReligionController;
use App\Domains\Contact\ManageReminders\Web\Controllers\ContactModuleReminderController;
use App\Domains\Contact\ManageTasks\Web\Controllers\ContactModuleTaskController;
use App\Domains\Settings\CancelAccount\Web\Controllers\CancelAccountController;
use App\Domains\Settings\ManageAddressTypes\Web\Controllers\PersonalizeAddressTypeController;
use App\Domains\Settings\ManageCallReasons\Web\Controllers\PersonalizeCallReasonsController;
use App\Domains\Settings\ManageCallReasons\Web\Controllers\PersonalizeCallReasonTypesController;
use App\Domains\Settings\ManageContactInformationTypes\Web\Controllers\PersonalizeContatInformationTypesController;
use App\Domains\Settings\ManageCurrencies\Web\Controllers\CurrencyController;
use App\Domains\Settings\ManageCurrencies\Web\Controllers\PersonalizeCurrencyController;
use App\Domains\Settings\ManageGenders\Web\Controllers\ManageGenderController;
use App\Domains\Settings\ManageGiftOccasions\Web\Controllers\PersonalizeGiftOccasionController;
use App\Domains\Settings\ManageGiftOccasions\Web\Controllers\PersonalizeGiftOccasionsPositionController;
use App\Domains\Settings\ManageGiftStates\Web\Controllers\PersonalizeGiftStateController;
use App\Domains\Settings\ManageGiftStates\Web\Controllers\PersonalizeGiftStatesPositionController;
use App\Domains\Settings\ManageGroupTypes\Web\Controllers\PersonalizeGroupTypeController;
use App\Domains\Settings\ManageGroupTypes\Web\Controllers\PersonalizeGroupTypePositionController;
use App\Domains\Settings\ManageGroupTypes\Web\Controllers\PersonalizeGroupTypeRoleController;
use App\Domains\Settings\ManageGroupTypes\Web\Controllers\PersonalizeGroupTypeRolePositionController;
use App\Domains\Settings\ManageModules\Web\Controllers\PersonalizeModulesController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\NotificationsController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\NotificationsLogController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\NotificationsTestController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\NotificationsToggleController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\NotificationsVerificationController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\TelegramNotificationsController;
use App\Domains\Settings\ManageNotificationChannels\Web\Controllers\TelegramWebhookController;
use App\Domains\Settings\ManagePersonalization\Web\Controllers\PersonalizeController;
use App\Domains\Settings\ManagePetCategories\Web\Controllers\PersonalizePetCategoriesController;
use App\Domains\Settings\ManagePostTemplates\Web\Controllers\PersonalizePostTemplateController;
use App\Domains\Settings\ManagePostTemplates\Web\Controllers\PersonalizePostTemplatePositionController;
use App\Domains\Settings\ManagePostTemplates\Web\Controllers\PersonalizePostTemplateSectionController;
use App\Domains\Settings\ManagePostTemplates\Web\Controllers\PersonalizePostTemplateSectionPositionController;
use App\Domains\Settings\ManagePronouns\Web\Controllers\PersonalizePronounController;
use App\Domains\Settings\ManageRelationshipTypes\Web\Controllers\PersonalizeRelationshipController;
use App\Domains\Settings\ManageRelationshipTypes\Web\Controllers\PersonalizeRelationshipTypeController;
use App\Domains\Settings\ManageReligion\Web\Controllers\PersonalizeReligionController;
use App\Domains\Settings\ManageReligion\Web\Controllers\PersonalizeReligionsPositionController;
use App\Domains\Settings\ManageSettings\Web\Controllers\SettingsController;
use App\Domains\Settings\ManageStorage\Web\Controllers\AccountStorageController;
use App\Domains\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePageModulesController;
use App\Domains\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePageModulesPositionController;
use App\Domains\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePagePositionController;
use App\Domains\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatePagesController;
use App\Domains\Settings\ManageTemplates\Web\Controllers\PersonalizeTemplatesController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesDateFormatController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesDistanceFormatController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesHelpController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesLocaleController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesMapsPreferenceController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesNameOrderController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesNumberFormatController;
use App\Domains\Settings\ManageUserPreferences\Web\Controllers\PreferencesTimezoneController;
use App\Domains\Settings\ManageUsers\Web\Controllers\UserController;
use App\Domains\Vault\ManageCalendar\Web\Controllers\VaultCalendarController;
use App\Domains\Vault\ManageCompanies\Web\Controllers\VaultCompanyController;
use App\Domains\Vault\ManageFiles\Web\Controllers\VaultFileController;
use App\Domains\Vault\ManageJournals\Web\Controllers\JournalController;
use App\Domains\Vault\ManageJournals\Web\Controllers\JournalMetricController;
use App\Domains\Vault\ManageJournals\Web\Controllers\JournalPhotoController;
use App\Domains\Vault\ManageJournals\Web\Controllers\PostController;
use App\Domains\Vault\ManageJournals\Web\Controllers\PostMetricController;
use App\Domains\Vault\ManageJournals\Web\Controllers\PostPhotoController;
use App\Domains\Vault\ManageJournals\Web\Controllers\PostSliceOfLifeController;
use App\Domains\Vault\ManageJournals\Web\Controllers\PostTagController;
use App\Domains\Vault\ManageJournals\Web\Controllers\SliceOfLifeController;
use App\Domains\Vault\ManageJournals\Web\Controllers\SliceOfLifeCoverImageController;
use App\Domains\Vault\ManageLifeMetrics\Web\Controllers\LifeMetricContactController;
use App\Domains\Vault\ManageLifeMetrics\Web\Controllers\LifeMetricController;
use App\Domains\Vault\ManageReports\Web\Controllers\ReportAddressesCitiesController;
use App\Domains\Vault\ManageReports\Web\Controllers\ReportAddressesController;
use App\Domains\Vault\ManageReports\Web\Controllers\ReportAddressesCountriesController;
use App\Domains\Vault\ManageReports\Web\Controllers\ReportImportantDateSummaryController;
use App\Domains\Vault\ManageReports\Web\Controllers\ReportIndexController;
use App\Domains\Vault\ManageReports\Web\Controllers\ReportMoodTrackingEventController;
use App\Domains\Vault\ManageTasks\Web\Controllers\VaultTaskController;
use App\Domains\Vault\ManageVault\Web\Controllers\VaultController;
use App\Domains\Vault\ManageVault\Web\Controllers\VaultDefaultTabOnDashboardController;
use App\Domains\Vault\ManageVault\Web\Controllers\VaultFeedController;
use App\Domains\Vault\ManageVault\Web\Controllers\VaultReminderController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsContactImportantDateTypeController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsLabelController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsLifeEventCategoriesController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsLifeEventCategoriesPositionController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsLifeEventTypesController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsLifeEventTypesPositionController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsMoodTrackingParameterController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsMoodTrackingParameterPositionController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsQuickFactTemplateController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsQuickFactTemplatePositionController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsTabVisibilityController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsTagController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsTemplateController;
use App\Domains\Vault\ManageVaultSettings\Web\Controllers\VaultSettingsUserController;
use App\Domains\Vault\Search\Web\Controllers\VaultContactSearchController;
use App\Domains\Vault\Search\Web\Controllers\VaultMostConsultedContactsController;
use App\Domains\Vault\Search\Web\Controllers\VaultSearchController;
use App\Http\Controllers\Auth\AcceptInvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SocialiteCallbackController;
use App\Http\Controllers\Profile\UserTokenController;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! Auth::check()) {
        return Redirect::route('login');
    }
    if (($vaults = Auth::user()->vaults)->count() === 1) {
        return Redirect::intended(route('vault.show', $vaults->first(), absolute: false));
    }

    return Redirect::intended(route('vault.index', absolute: false));
})->name('home');
Route::post('closeBeta', [LoginController::class, 'closeBeta'])->name('close_beta');

// Redirect .well-known urls (https://en.wikipedia.org/wiki/List_of_/.well-known/_services_offered_by_webservers)
Route::permanentRedirect('/.well-known/carddav', '/dav');
Route::permanentRedirect('/.well-known/caldav', '/dav');
Route::permanentRedirect('/.well-known/security.txt', '/security.txt');

Route::middleware(['throttle:oauth2-socialite'])->group(function () {
    Route::get('auth/{driver}', [SocialiteCallbackController::class, 'login'])->name('login.provider');
    Route::get('auth/{driver}/callback', [SocialiteCallbackController::class, 'callback']);
    Route::post('auth/{driver}/callback', [SocialiteCallbackController::class, 'callback']);
});

Route::get('invitation/{code}', [AcceptInvitationController::class, 'show'])->name('invitation.show');
Route::post('invitation', [AcceptInvitationController::class, 'store'])->name('invitation.store');

if (config('services.telegram-bot-api.token') !== null) {
    Route::post(
        '/telegram/webhook/'.config('services.telegram-bot-api.webhook'),
        [TelegramWebhookController::class, 'store']
    );
}

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // vaults
    Route::prefix('vaults')->group(function () {
        Route::get('', [VaultController::class, 'index'])->name('vault.index');
        Route::get('create', [VaultController::class, 'create'])->name('vault.create');
        Route::post('', [VaultController::class, 'store'])->name('vault.store');
        Route::get('{vault}', [VaultController::class, 'show'])->name('vault.show');
        Route::get('{vault}/edit', [VaultController::class, 'edit'])->name('vault.edit');
        Route::put('{vault}', [VaultController::class, 'update'])->name('vault.update');
        Route::delete('{vault}', [VaultController::class, 'destroy'])->name('vault.destroy');

        Route::middleware('can:vault-viewer,vault')->prefix('{vault}')->group(function () {
            // update dashboard's default tab
            Route::put('defaultTab', [VaultDefaultTabOnDashboardController::class, 'update'])->name('vault.default_tab.update');

            // calendar
            Route::get('calendar', [VaultCalendarController::class, 'index'])->name('vault.calendar.index');
            Route::get('calendar/years/{year}/months/{month}', [VaultCalendarController::class, 'month'])->name('vault.calendar.month');
            Route::get('calendar/years/{year}/months/{month}/days/{day}', [VaultCalendarController::class, 'day'])->name('vault.calendar.day');

            // reminders
            Route::get('reminders', [VaultReminderController::class, 'index'])->name('vault.reminder.index');

            // vault feed entries
            Route::get('feed', [VaultFeedController::class, 'show'])->name('vault.feed.show');

            // tasks
            Route::get('tasks', [VaultTaskController::class, 'index'])->name('vault.tasks.index');

            // reports
            Route::prefix('reports')->group(function () {
                Route::get('', [ReportIndexController::class, 'index'])->name('vault.reports.index');

                // contact addresses
                Route::get('addresses', [ReportAddressesController::class, 'index'])->name('vault.reports.addresses.index');
                Route::get('addresses/city/{city}', [ReportAddressesCitiesController::class, 'show'])->name('vault.reports.addresses.cities.show');
                Route::get('addresses/country/{country}', [ReportAddressesCountriesController::class, 'show'])->name('vault.reports.addresses.countries.show');

                // mood tracking event
                Route::get('moodTrackingEvents', [ReportMoodTrackingEventController::class, 'index'])->name('vault.reports.mood_tracking_events.index');

                // important date summary
                Route::get('importantDates', [ReportImportantDateSummaryController::class, 'index'])->name('vault.reports.important_dates.index');
            });

            // life metrics
            Route::post('lifeMetrics', [LifeMetricController::class, 'store'])->name('vault.life_metrics.store');
            Route::put('lifeMetrics/{metric}', [LifeMetricController::class, 'update'])->name('vault.life_metrics.update');
            Route::post('lifeMetrics/{metric}', [LifeMetricContactController::class, 'store'])->name('vault.life_metrics.contact.store');
            Route::delete('lifeMetrics/{metric}', [LifeMetricController::class, 'destroy'])->name('vault.life_metrics.destroy');

            // vault contacts
            Route::prefix('contacts')->group(function () {
                Route::get('', [ContactController::class, 'index'])->name('contact.index');
                Route::get('labels/{label}', [ContactLabelController::class, 'index'])->name('contact.label.index');
                Route::put('sort', [ContactSortController::class, 'update'])->name('contact.sort.update');

                // create a contact
                Route::get('create', [ContactController::class, 'create'])->name('contact.create');
                Route::post('', [ContactController::class, 'store'])->name('contact.store');

                // contact page
                Route::middleware('can:contact-owner,vault,contact')->prefix('{contact}')->group(function () {
                    // general page information
                    Route::get('', [ContactController::class, 'show'])->name('contact.show');
                    Route::get('edit', [ContactController::class, 'edit'])->name('contact.edit');
                    Route::post('', [ContactController::class, 'update'])->name('contact.update');
                    Route::delete('', [ContactController::class, 'destroy'])->name('contact.destroy');

                    Route::post('vcard', [ContactVCardController::class, 'download'])->name('contact.vcard.download')->withoutMiddleware([HandleInertiaRequests::class]);

                    // quick facts
                    Route::get('quickFacts/{template}', [ContactQuickFactController::class, 'show'])->name('contact.quick_fact.show');
                    Route::post('quickFacts/{template}', [ContactQuickFactController::class, 'store'])->name('contact.quick_fact.store');
                    Route::put('quickFacts/toggle', [ContactQuickFactToggleController::class, 'update'])->name('contact.quick_fact.toggle');
                    Route::put('quickFacts/{template}/{quickFact}', [ContactQuickFactController::class, 'update'])->name('contact.quick_fact.update');
                    Route::delete('quickFacts/{template}/{quickFact}', [ContactQuickFactController::class, 'destroy'])->name('contact.quick_fact.destroy');

                    // toggle archive/favorite
                    Route::put('toggle', [ContactArchiveController::class, 'update'])->name('contact.archive.update');
                    Route::put('toggle-favorite', [ContactFavoriteController::class, 'update'])->name('contact.favorite.update');

                    // move contact to another vault
                    Route::get('move', [ContactMoveController::class, 'show'])->name('contact.move.show');
                    Route::post('move', [ContactMoveController::class, 'store'])->name('contact.move.store');

                    // template
                    Route::get('update-template', [ContactNoTemplateController::class, 'show'])->name('contact.blank');
                    Route::put('template', [ContactTemplateController::class, 'update'])->name('contact.template.update');

                    // get the proper tab
                    Route::get('tabs/{slug}', [ContactPageController::class, 'show'])->name('contact.page.show');

                    // avatar
                    Route::put('avatar', [ModuleAvatarController::class, 'update'])->name('contact.avatar.update');
                    Route::delete('avatar', [ModuleAvatarController::class, 'destroy'])->name('contact.avatar.destroy');

                    // contact feed entries
                    Route::get('feed', [ContactFeedController::class, 'show'])->name('contact.feed.show');

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

                    // goals
                    Route::get('goals/{goal}', [ContactGoalController::class, 'show'])->name('contact.goal.show');
                    Route::post('goals', [ContactModuleGoalController::class, 'store'])->name('contact.goal.store');
                    Route::put('goals/{goal}', [ContactGoalController::class, 'update'])->name('contact.goal.update');
                    Route::put('goals/{goal}/streaks', [ContactModuleStreakController::class, 'update'])->name('contact.goal.streak.update');
                    Route::delete('goals/{goal}', [ContactGoalController::class, 'destroy'])->name('contact.goal.destroy');

                    // labels
                    Route::post('labels', [ContactModuleLabelController::class, 'store'])->name('contact.label.store');
                    Route::put('labels/{label}', [ContactModuleLabelController::class, 'update'])->name('contact.label.update');
                    Route::delete('labels/{label}', [ContactModuleLabelController::class, 'destroy'])->name('contact.label.destroy');

                    // reminders
                    Route::post('reminders', [ContactModuleReminderController::class, 'store'])->name('contact.reminder.store');
                    Route::put('reminders/{reminder}', [ContactModuleReminderController::class, 'update'])->name('contact.reminder.update');
                    Route::delete('reminders/{reminder}', [ContactModuleReminderController::class, 'destroy'])->name('contact.reminder.destroy');

                    // addresses
                    Route::post('addresses', [ContactModuleAddressController::class, 'store'])->name('contact.address.store');
                    Route::put('addresses/{address}', [ContactModuleAddressController::class, 'update'])->name('contact.address.update');
                    Route::delete('addresses/{address}', [ContactModuleAddressController::class, 'destroy'])->name('contact.address.destroy');
                    Route::get('addresses/{address}/image/{width}x{height}', [ContactModuleAddressImageController::class, 'show'])
                        ->where('width', '.*')
                        ->where('height', '.*')
                        ->name('contact.address.image.show');

                    // contact information
                    Route::post('contactInformation', [ContactInformationController::class, 'store'])->name('contact.contact_information.store');
                    Route::put('contactInformation/{info}', [ContactInformationController::class, 'update'])->name('contact.contact_information.update');
                    Route::delete('contactInformation/{info}', [ContactInformationController::class, 'destroy'])->name('contact.contact_information.destroy');

                    // loans
                    Route::post('loans', [ContactModuleLoanController::class, 'store'])->name('contact.loan.store');
                    Route::put('loans/{loan}', [ContactModuleLoanController::class, 'update'])->name('contact.loan.update');
                    Route::put('loans/{loan}/toggle', [ContactModuleToggleLoanController::class, 'update'])->name('contact.loan.toggle');
                    Route::delete('loans/{loan}', [ContactModuleLoanController::class, 'destroy'])->name('contact.loan.destroy');

                    // job information
                    Route::get('companies/list', [ContactModuleJobInformationController::class, 'index'])->name('contact.companies.list.index');
                    Route::put('jobInformation', [ContactModuleJobInformationController::class, 'update'])->name('contact.job_information.update');
                    Route::delete('jobInformation', [ContactModuleJobInformationController::class, 'destroy'])->name('contact.job_information.destroy');

                    // religion
                    Route::put('religion', [ContactModuleReligionController::class, 'update'])->name('contact.religion.update');

                    // relationships
                    Route::get('relationships/create', [ContactRelationshipsController::class, 'create'])->name('contact.relationships.create');
                    Route::post('relationships', [ContactRelationshipsController::class, 'store'])->name('contact.relationships.store');
                    Route::put('relationships/{relationship}', [ContactRelationshipsController::class, 'update'])->name('contact.relationships.update');

                    // pets
                    Route::post('pets', [ContactModulePetController::class, 'store'])->name('contact.pet.store');
                    Route::put('pets/{pet}', [ContactModulePetController::class, 'update'])->name('contact.pet.update');
                    Route::delete('pets/{pet}', [ContactModulePetController::class, 'destroy'])->name('contact.pet.destroy');

                    // documents
                    Route::post('documents', [ContactModuleDocumentController::class, 'store'])->name('contact.document.store');
                    Route::delete('documents/{document}', [ContactModuleDocumentController::class, 'destroy'])->name('contact.document.destroy');

                    // photos
                    Route::get('photos', [ContactPhotoController::class, 'index'])->name('contact.photo.index');
                    Route::get('photos/{photo}', [ContactPhotoController::class, 'show'])->name('contact.photo.show');
                    Route::post('photos', [ContactModulePhotoController::class, 'store'])->name('contact.photo.store');
                    Route::delete('photos/{photo}', [ContactModulePhotoController::class, 'destroy'])->name('contact.photo.destroy');

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

                    // groups
                    Route::post('groups', [ContactModuleGroupController::class, 'store'])->name('contact.group.store');
                    Route::delete('groups/{group}', [ContactModuleGroupController::class, 'destroy'])->name('contact.group.destroy');

                    // timeline events (which contain life events)
                    Route::get('timelineEvents', [ContactModuleTimelineEventController::class, 'index'])->name('contact.timeline_event.index');
                    Route::post('timelineEvents', [ContactModuleTimelineEventController::class, 'store'])->name('contact.timeline_event.store');
                    Route::post('timelineEvents/{timelineEvent}/toggle', [ToggleTimelineEventController::class, 'store'])->name('contact.timeline_event.toggle');
                    Route::post('timelineEvents/{timelineEvent}/lifeEvents', [ContactModuleLifeEventController::class, 'store'])->name('contact.life_event.store');
                    Route::delete('timelineEvents/{timelineEvent}', [ContactModuleTimelineEventController::class, 'destroy'])->name('contact.timeline_event.destroy');
                    Route::put('timelineEvents/{timelineEvent}/lifeEvents/{lifeEvent}', [ContactModuleLifeEventController::class, 'edit'])->name('contact.life_event.edit');
                    Route::post('timelineEvents/{timelineEvent}/lifeEvents/{lifeEvent}/toggle', [ToggleLifeEventController::class, 'store'])->name('contact.life_event.toggle');
                    Route::delete('timelineEvents/{timelineEvent}/lifeEvents/{lifeEvent}', [ContactModuleLifeEventController::class, 'destroy'])->name('contact.life_event.destroy');

                    // mood tracking events
                    Route::post('moodTrackingEvents', [ContactMoodTrackingEventsController::class, 'store'])->name('contact.mood_tracking_event.store');
                });
            });

            // group page
            Route::get('groups', [GroupController::class, 'index'])->name('group.index');
            Route::middleware('can:group-owner,vault,group')->prefix('groups')->group(function () {
                Route::get('{group}', [GroupController::class, 'show'])->name('group.show');
                Route::get('{group}/edit', [GroupController::class, 'edit'])->name('group.edit');
                Route::put('{group}', [GroupController::class, 'update'])->name('group.update');
                Route::delete('{group}', [GroupController::class, 'destroy'])->name('group.destroy');
            });

            // journal page
            Route::prefix('journals')->group(function () {
                Route::get('', [JournalController::class, 'index'])->name('journal.index');

                // create a journal
                Route::get('/create', [JournalController::class, 'create'])->name('journal.create');
                Route::post('', [JournalController::class, 'store'])->name('journal.store');

                Route::middleware('can:journal-owner,vault,journal')->prefix('{journal}')->group(function () {
                    Route::get('', [JournalController::class, 'show'])->name('journal.show');
                    Route::get('photos', [JournalPhotoController::class, 'index'])->name('journal.photo.index');
                    Route::get('years/{year}', [JournalController::class, 'year'])->name('journal.year');
                    Route::get('edit', [JournalController::class, 'edit'])->name('journal.edit');
                    Route::put('', [JournalController::class, 'update'])->name('journal.update');
                    Route::delete('', [JournalController::class, 'destroy'])->name('journal.destroy');

                    // posts
                    Route::get('posts/create', [PostController::class, 'create'])->name('post.create');
                    Route::get('posts/template/{template}', [PostController::class, 'store'])->name('post.store');

                    // details of a post
                    Route::middleware('can:post-owner,journal,post')->prefix('posts/{post}')->group(function () {
                        Route::get('', [PostController::class, 'show'])->name('post.show');
                        Route::get('edit', [PostController::class, 'edit'])->name('post.edit');
                        Route::put('update', [PostController::class, 'update'])->name('post.update');
                        Route::post('photos', [PostPhotoController::class, 'store'])->name('post.photos.store');
                        Route::delete('photos/{photo}', [PostPhotoController::class, 'destroy'])->name('post.photos.destroy');
                        Route::delete('', [PostController::class, 'destroy'])->name('post.destroy');

                        // tags
                        Route::post('tags', [PostTagController::class, 'store'])->name('post.tag.store');
                        Route::put('tags/{tag}', [PostTagController::class, 'update'])->name('post.tag.update');
                        Route::delete('tags/{tag}', [PostTagController::class, 'destroy'])->name('post.tag.destroy');

                        // slices of life
                        Route::put('slices', [PostSliceOfLifeController::class, 'update'])->name('post.slices.update');
                        Route::delete('slices', [PostSliceOfLifeController::class, 'destroy'])->name('post.slices.destroy');

                        // post metrics
                        Route::post('metrics', [PostMetricController::class, 'store'])->name('post.metrics.store');
                        Route::delete('metrics/{metric}', [PostMetricController::class, 'destroy'])->name('post.metrics.destroy');
                    });

                    // slices of life
                    Route::get('slices', [SliceOfLifeController::class, 'index'])->name('slices.index');
                    Route::post('slices', [SliceOfLifeController::class, 'store'])->name('slices.store');

                    Route::middleware('can:slice-owner,journal,slice')->prefix('slices/{slice}')->group(function () {
                        Route::get('', [SliceOfLifeController::class, 'show'])->name('slices.show');
                        Route::get('edit', [SliceOfLifeController::class, 'edit'])->name('slices.edit');
                        Route::put('', [SliceOfLifeController::class, 'update'])->name('slices.update');
                        Route::put('cover', [SliceOfLifeCoverImageController::class, 'update'])->name('slices.cover.update');
                        Route::delete('cover', [SliceOfLifeCoverImageController::class, 'destroy'])->name('slices.cover.destroy');
                        Route::delete('', [SliceOfLifeController::class, 'destroy'])->name('slices.destroy');
                    });

                    //  journal metrics
                    Route::get('metrics', [JournalMetricController::class, 'index'])->name('journal_metrics.index');
                    Route::post('metrics', [JournalMetricController::class, 'store'])->name('journal_metrics.store');
                    Route::delete('metrics/{metric}', [JournalMetricController::class, 'destroy'])->name('journal_metrics.destroy');
                });
            });

            // vault files
            Route::prefix('files')->name('vault.files.')->group(function () {
                Route::get('', [VaultFileController::class, 'index'])->name('index');
                Route::get('photos', [VaultFileController::class, 'photos'])->name('photos');
                Route::get('documents', [VaultFileController::class, 'documents'])->name('documents');
                Route::get('avatars', [VaultFileController::class, 'avatars'])->name('avatars');

                Route::delete('{file}', [VaultFileController::class, 'destroy'])->name('destroy');
            });

            // companies
            Route::prefix('companies')->name('vault.companies.')->group(function () {
                Route::get('', [VaultCompanyController::class, 'index'])->name('index');
                Route::get('{company}', [VaultCompanyController::class, 'show'])->name('show');
            });

            // vault settings
            Route::middleware('can:vault-manager,vault')->group(function () {
                Route::get('settings', [VaultSettingsController::class, 'index'])->name('vault.settings.index');
                Route::put('settings', [VaultSettingsController::class, 'update'])->name('vault.settings.update');
                Route::put('settings/template', [VaultSettingsTemplateController::class, 'update'])->name('vault.settings.template.update');
                Route::post('settings/users', [VaultSettingsUserController::class, 'store'])->name('vault.settings.user.store');
                Route::put('settings/users/{user}', [VaultSettingsUserController::class, 'update'])->name('vault.settings.user.update');
                Route::delete('settings/users/{user}', [VaultSettingsUserController::class, 'destroy'])->name('vault.settings.user.destroy');

                // labels
                Route::get('settings/labels', [VaultSettingsLabelController::class, 'index'])->name('vault.settings.label.index');
                Route::post('settings/labels', [VaultSettingsLabelController::class, 'store'])->name('vault.settings.label.store');
                Route::put('settings/labels/{label}', [VaultSettingsLabelController::class, 'update'])->name('vault.settings.label.update');
                Route::delete('settings/labels/{label}', [VaultSettingsLabelController::class, 'destroy'])->name('vault.settings.label.destroy');

                // tags
                Route::get('settings/tags', [VaultSettingsTagController::class, 'index'])->name('vault.settings.tag.index');
                Route::post('settings/tags', [VaultSettingsTagController::class, 'store'])->name('vault.settings.tag.store');
                Route::put('settings/tags/{tag}', [VaultSettingsTagController::class, 'update'])->name('vault.settings.tag.update');
                Route::delete('settings/tags/{tag}', [VaultSettingsTagController::class, 'destroy'])->name('vault.settings.tag.destroy');

                // contact important date types
                Route::post('settings/contactImportantDateTypes', [VaultSettingsContactImportantDateTypeController::class, 'store'])->name('vault.settings.important_date_type.store');
                Route::put('settings/contactImportantDateTypes/{type}', [VaultSettingsContactImportantDateTypeController::class, 'update'])->name('vault.settings.important_date_type.update');
                Route::delete('settings/contactImportantDateTypes/{type}', [VaultSettingsContactImportantDateTypeController::class, 'destroy'])->name('vault.settings.important_date_type.destroy');

                // tab visibility
                Route::put('settings/visibility', [VaultSettingsTabVisibilityController::class, 'update'])->name('vault.settings.tab.update');

                // mood tracking parameters
                Route::post('settings/moodTrackingParameters', [VaultSettingsMoodTrackingParameterController::class, 'store'])->name('vault.settings.mood_tracking_parameter.store');
                Route::put('settings/moodTrackingParameters/{parameter}', [VaultSettingsMoodTrackingParameterController::class, 'update'])->name('vault.settings.mood_tracking_parameter.update');
                Route::put('settings/moodTrackingParameters/{parameter}/order', [VaultSettingsMoodTrackingParameterPositionController::class, 'update'])->name('vault.settings.mood_tracking_parameter.order.update');
                Route::delete('settings/moodTrackingParameters/{parameter}', [VaultSettingsMoodTrackingParameterController::class, 'destroy'])->name('vault.settings.mood_tracking_parameter.destroy');

                // life event categories
                Route::post('settings/lifeEventCategories', [VaultSettingsLifeEventCategoriesController::class, 'store'])->name('vault.settings.life_event_categories.store');
                Route::put('settings/lifeEventCategories/{lifeEventCategory}', [VaultSettingsLifeEventCategoriesController::class, 'update'])->name('vault.settings.life_event_categories.update');
                Route::delete('settings/lifeEventCategories/{lifeEventCategory}', [VaultSettingsLifeEventCategoriesController::class, 'destroy'])->name('vault.settings.life_event_categories.destroy');
                Route::post('settings/lifeEventCategories/{lifeEventCategory}/order', [VaultSettingsLifeEventCategoriesPositionController::class, 'update'])->name('vault.settings.life_event_categories.order.update');

                // life event types
                Route::post('settings/lifeEventCategories/{lifeEventCategory}/lifeEventTypes', [VaultSettingsLifeEventTypesController::class, 'store'])->name('vault.settings.life_event_types.store');
                Route::put('settings/lifeEventCategories/{lifeEventCategory}/lifeEventTypes/{lifeEventType}', [VaultSettingsLifeEventTypesController::class, 'update'])->name('vault.settings.life_event_types.update');
                Route::delete('settings/lifeEventCategories/{lifeEventCategory}/lifeEventTypes/{lifeEventType}', [VaultSettingsLifeEventTypesController::class, 'destroy'])->name('vault.settings.life_event_types.destroy');
                Route::post('settings/lifeEventCategories/{lifeEventCategory}/lifeEventTypes/{lifeEventType}/order', [VaultSettingsLifeEventTypesPositionController::class, 'update'])->name('vault.settings.life_event_types.order.update');

                // quick fact templates
                Route::post('settings/quickFactTemplates', [VaultSettingsQuickFactTemplateController::class, 'store'])->name('vault.settings.quick_fact_templates.store');
                Route::put('settings/quickFactTemplates/{template}', [VaultSettingsQuickFactTemplateController::class, 'update'])->name('vault.settings.quick_fact_templates.update');
                Route::put('settings/quickFactTemplates/{template}/order', [VaultSettingsQuickFactTemplatePositionController::class, 'update'])->name('vault.settings.quick_fact_templates.order.update');
                Route::delete('settings/quickFactTemplates/{template}', [VaultSettingsQuickFactTemplateController::class, 'destroy'])->name('vault.settings.quick_fact_templates.destroy');
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
            Route::post('distance', [PreferencesDistanceFormatController::class, 'store'])->name('distance.store');
            Route::post('maps', [PreferencesMapsPreferenceController::class, 'store'])->name('maps.store');
            Route::post('locale', [PreferencesLocaleController::class, 'store'])->name('locale.store');
            Route::post('help', [PreferencesHelpController::class, 'store'])->name('help.store');
        });

        // notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('', [NotificationsController::class, 'index'])->name('index');
            Route::post('', [NotificationsController::class, 'store'])->name('store');
            Route::post('telegram', [TelegramNotificationsController::class, 'store'])->name('telegram.store');
            Route::get('{notification}/verify/{uuid}', [NotificationsVerificationController::class, 'store'])->name('verification.store');
            Route::post('{notification}/test', [NotificationsTestController::class, 'store'])->name('test.store');
            Route::put('{notification}/toggle', [NotificationsToggleController::class, 'update'])->name('toggle.update');
            Route::delete('{notification}', [NotificationsController::class, 'destroy'])->name('destroy');

            // notification logs
            Route::get('{notification}/logs', [NotificationsLogController::class, 'index'])->name('log.index');
        });

        // only for administrators
        Route::middleware('can:administrator')->group(function () {
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

                // gift occasions
                Route::get('giftOccasions', [PersonalizeGiftOccasionController::class, 'index'])->name('gift_occasions.index');
                Route::post('giftOccasions', [PersonalizeGiftOccasionController::class, 'store'])->name('gift_occasions.store');
                Route::put('giftOccasions/{giftOccasion}', [PersonalizeGiftOccasionController::class, 'update'])->name('gift_occasions.update');
                Route::delete('giftOccasions/{giftOccasion}', [PersonalizeGiftOccasionController::class, 'destroy'])->name('gift_occasions.destroy');
                Route::post('giftOccasions/{giftOccasion}/position', [PersonalizeGiftOccasionsPositionController::class, 'update'])->name('gift_occasions.order.update');

                // gift stages
                Route::get('giftStates', [PersonalizeGiftStateController::class, 'index'])->name('gift_states.index');
                Route::post('giftStates', [PersonalizeGiftStateController::class, 'store'])->name('gift_states.store');
                Route::put('giftStates/{giftState}', [PersonalizeGiftStateController::class, 'update'])->name('gift_states.update');
                Route::delete('giftStates/{giftState}', [PersonalizeGiftStateController::class, 'destroy'])->name('gift_states.destroy');
                Route::post('giftStates/{giftState}/position', [PersonalizeGiftStatesPositionController::class, 'update'])->name('gift_states.order.update');

                // post templates
                Route::get('postTemplates', [PersonalizePostTemplateController::class, 'index'])->name('post_templates.index');
                Route::post('postTemplates', [PersonalizePostTemplateController::class, 'store'])->name('post_templates.store');
                Route::put('postTemplates/{postTemplate}', [PersonalizePostTemplateController::class, 'update'])->name('post_templates.update');
                Route::delete('postTemplates/{postTemplate}', [PersonalizePostTemplateController::class, 'destroy'])->name('post_templates.destroy');
                Route::post('postTemplates/{postTemplate}/position', [PersonalizePostTemplatePositionController::class, 'update'])->name('post_templates.order.update');

                // post template sections
                Route::post('postTemplates/{postTemplate}/sections', [PersonalizePostTemplateSectionController::class, 'store'])->name('post_templates.section.store');
                Route::put('postTemplates/{postTemplate}/sections/{section}', [PersonalizePostTemplateSectionController::class, 'update'])->name('post_templates.section.update');
                Route::delete('postTemplates/{postTemplate}/sections/{section}', [PersonalizePostTemplateSectionController::class, 'destroy'])->name('post_templates.section.destroy');
                Route::post('postTemplates/{postTemplate}/sections/{section}/position', [PersonalizePostTemplateSectionPositionController::class, 'update'])->name('post_templates.section.order.update');

                // group types
                Route::get('groupTypes', [PersonalizeGroupTypeController::class, 'index'])->name('group_types.index');
                Route::post('groupTypes', [PersonalizeGroupTypeController::class, 'store'])->name('group_types.store');
                Route::put('groupTypes/{type}', [PersonalizeGroupTypeController::class, 'update'])->name('group_types.update');
                Route::delete('groupTypes/{type}', [PersonalizeGroupTypeController::class, 'destroy'])->name('group_types.destroy');
                Route::post('groupTypes/{type}/position', [PersonalizeGroupTypePositionController::class, 'update'])->name('group_types.order.update');

                // group type roles
                Route::post('groupTypes/{type}/groupTypeRoles', [PersonalizeGroupTypeRoleController::class, 'store'])->name('group_types.roles.store');
                Route::put('groupTypes/{type}/groupTypeRoles/{role}', [PersonalizeGroupTypeRoleController::class, 'update'])->name('group_types.roles.update');
                Route::delete('groupTypes/{type}/groupTypeRoles/{role}', [PersonalizeGroupTypeRoleController::class, 'destroy'])->name('group_types.roles.destroy');
                Route::post('groupTypes/{type}/groupTypeRoles/{role}/position', [PersonalizeGroupTypeRolePositionController::class, 'update'])->name('group_types.roles.order.update');

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

                // religions
                Route::get('religions', [PersonalizeReligionController::class, 'index'])->name('religions.index');
                Route::post('religions', [PersonalizeReligionController::class, 'store'])->name('religions.store');
                Route::put('religions/{religion}', [PersonalizeReligionController::class, 'update'])->name('religions.update');
                Route::delete('religions/{religion}', [PersonalizeReligionController::class, 'destroy'])->name('religions.destroy');
                Route::post('religions/{religion}/position', [PersonalizeReligionsPositionController::class, 'update'])->name('religions.order.update');
            });

            // storage
            Route::get('storage', [AccountStorageController::class, 'index'])->name('storage.index');

            // cancel
            Route::get('cancel', [CancelAccountController::class, 'index'])->name('cancel.index');
            Route::put('cancel', [CancelAccountController::class, 'destroy'])->name('cancel.destroy');
        });
    });

    // General stuff called by everyone/everywhere
    Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');

    // User & Profile...
    Route::delete('auth/{driver}', [UserTokenController::class, 'destroy'])->name('provider.delete');
});
