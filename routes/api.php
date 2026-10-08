<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ChannelProofController as ApiChannelProofController;
use App\Http\Controllers\Api\ChannelTestController as ApiChannelTestController;
use App\Http\Controllers\Api\CheckoutSessionController;
use App\Http\Controllers\Api\ContactAvatarController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactDuplicateController;
use App\Http\Controllers\Api\ContactDuplicateDismissalController;
use App\Http\Controllers\Api\ContactFavouriteController;
use App\Http\Controllers\Api\ContactListColumnController;
use App\Http\Controllers\Api\ContactMeController;
use App\Http\Controllers\Api\ContactMergeController;
use App\Http\Controllers\Api\ContactNameController;
use App\Http\Controllers\Api\ContactNameRepresentationController;
use App\Http\Controllers\Api\ContactReminderController;
use App\Http\Controllers\Api\ContactReminderOccurrenceController;
use App\Http\Controllers\Api\ContactStructureVersionController;
use App\Http\Controllers\Api\ContactVCardController;
use App\Http\Controllers\Api\EmailSentController;
use App\Http\Controllers\Api\EntityController;
use App\Http\Controllers\Api\EntityTypeActivationController;
use App\Http\Controllers\Api\EntityTypeController;
use App\Http\Controllers\Api\EntityTypePositionController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldPositionController;
use App\Http\Controllers\Api\LifeEventCategoryActivationController;
use App\Http\Controllers\Api\LifeEventCategoryController;
use App\Http\Controllers\Api\LifeEventCategoryPositionController;
use App\Http\Controllers\Api\LifeEventCategoryTypeController;
use App\Http\Controllers\Api\LifeEventTypeActivationController;
use App\Http\Controllers\Api\LifeEventTypeController;
use App\Http\Controllers\Api\LifeEventTypeFieldController;
use App\Http\Controllers\Api\LifeEventTypePositionController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MeContactListViewController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeTimezoneController;
use App\Http\Controllers\Api\MeWelcomeController;
use App\Http\Controllers\Api\NotificationChannelController as ApiNotificationChannelController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController as ApiNotificationPreferenceController;
use App\Http\Controllers\Api\NotificationReadController as ApiNotificationReadController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PortalSessionController;
use App\Http\Controllers\Api\RelationshipCategoryActivationController;
use App\Http\Controllers\Api\RelationshipCategoryController;
use App\Http\Controllers\Api\RelationshipCategoryPositionController;
use App\Http\Controllers\Api\RelationshipCategoryTypeController;
use App\Http\Controllers\Api\RelationshipTypeActivationController;
use App\Http\Controllers\Api\RelationshipTypeController;
use App\Http\Controllers\Api\RelationshipTypePositionController;
use App\Http\Controllers\Api\ReminderOccurrenceAnswerController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\StructureVersionController;
use App\Http\Controllers\Api\StructureVersionPageController;
use App\Http\Controllers\Api\StructureVersionPagePositionController;
use App\Http\Controllers\Api\StructureVersionPublicationController;
use App\Http\Controllers\Api\StructureVersionSectionController;
use App\Http\Controllers\Api\StructureVersionSectionPageController;
use App\Http\Controllers\Api\StructureVersionSectionPositionController;
use App\Http\Controllers\Api\SubscriptionCancellationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TemplateActivationController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TemplateDuplicateController;
use App\Http\Controllers\Api\TemplatePositionController;
use App\Http\Controllers\Api\VaultController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('status', [StatusController::class, 'show'])->middleware('throttle:60,1')->name('status');

    Route::post('register', [RegistrationController::class, 'store'])->middleware(['registration', 'throttle:6,1'])->name('register');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

    Route::middleware(['auth:sanctum'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');
        Route::put('me/timezone', [MeTimezoneController::class, 'update'])->name('me.timezone.update');
        Route::put('me/contact-list-view', [MeContactListViewController::class, 'update'])->name('me.contactListView.update');
        Route::put('me/welcome', [MeWelcomeController::class, 'update'])->name('me.welcome.update');

        Route::get('billing', [BillingController::class, 'show'])->name('billing');
        Route::get('billing/payments', [PaymentController::class, 'index'])->name('billing.payments');
        Route::get('billing/payments/{payment}', [PaymentController::class, 'show'])->where('payment', '[A-Za-z0-9_]+')->name('billing.payments.show');
        Route::post('billing/checkout-sessions', [CheckoutSessionController::class, 'store'])->name('billing.checkoutSessions.create');
        Route::put('billing/subscription', [SubscriptionController::class, 'update'])->name('billing.subscription.update');
        Route::delete('billing/subscription', [SubscriptionController::class, 'destroy'])->name('billing.subscription.destroy');
        Route::delete('billing/subscription/cancellation', [SubscriptionCancellationController::class, 'destroy'])->name('billing.subscription.cancellation.destroy');
        Route::post('billing/portal-sessions', [PortalSessionController::class, 'store'])->name('billing.portalSessions.create');

        Route::get('api-keys', [ApiKeyController::class, 'index'])->name('apiKeys');
        Route::get('api-keys/{apiKey}', [ApiKeyController::class, 'show'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.show');
        Route::post('api-keys', [ApiKeyController::class, 'create'])->name('apiKeys.create');
        Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.destroy');

        Route::get('notifications', [ApiNotificationController::class, 'index'])->name('notifications');
        Route::put('notifications/read', [ApiNotificationReadController::class, 'updateAll'])->name('notifications.readAll.update');
        Route::get('notifications/{notification}', [ApiNotificationController::class, 'show'])->where('notification', '[0-9a-fA-F-]{36}')->name('notifications.show');
        Route::put('notifications/{notification}/read', [ApiNotificationReadController::class, 'update'])->where('notification', '[0-9a-fA-F-]{36}')->name('notifications.read.update');

        Route::get('notification-channels', [ApiNotificationChannelController::class, 'index'])->name('notificationChannels');
        Route::get('notification-channels/{channel}', [ApiNotificationChannelController::class, 'show'])->name('notificationChannels.show');
        Route::put('notification-channels/{channel}', [ApiNotificationChannelController::class, 'update'])->name('notificationChannels.update');
        Route::post('notification-channels/{channel}/proof', [ApiChannelProofController::class, 'create'])->name('notificationChannels.proof.store');
        Route::put('notification-channels/{channel}/proof', [ApiChannelProofController::class, 'update'])->name('notificationChannels.proof.update');
        Route::post('notification-channels/{channel}/test', [ApiChannelTestController::class, 'create'])->name('notificationChannels.test.store');

        Route::get('notification-preferences', [ApiNotificationPreferenceController::class, 'show'])->name('notificationPreferences.show');
        Route::put('notification-preferences', [ApiNotificationPreferenceController::class, 'update'])->name('notificationPreferences.update');

        Route::get('logs', [LogController::class, 'index'])->name('logs');
        Route::get('logs/{log}', [LogController::class, 'show'])->where('log', '[1-9][0-9]*')->name('logs.show');

        Route::get('emails-sent', [EmailSentController::class, 'index'])->name('emailsSent');
        Route::get('emails-sent/{emailSent}', [EmailSentController::class, 'show'])->where('emailSent', '[1-9][0-9]*')->name('emailsSent.show');

        Route::get('vaults', [VaultController::class, 'index'])->name('vaults');
        Route::get('vaults/{vault}', [VaultController::class, 'show'])->name('vaults.show');
        Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
        Route::put('vaults/{vault}', [VaultController::class, 'update'])->name('vaults.update');
        Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->name('vaults.destroy');

        Route::get('entity-types', [EntityTypeController::class, 'index'])->name('entityTypes');
        Route::get('entity-types/{entityType}', [EntityTypeController::class, 'show'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.show');
        Route::post('entity-types', [EntityTypeController::class, 'create'])->name('entityTypes.create');
        Route::put('entity-types/{entityType}', [EntityTypeController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.update');
        Route::delete('entity-types/{entityType}', [EntityTypeController::class, 'destroy'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.destroy');

        Route::put('entity-types/{entityType}/activation', [EntityTypeActivationController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.activation.update');
        Route::put('entity-types/{entityType}/position', [EntityTypePositionController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.position.update');
        Route::get('vaults/{vault}/entity-types/{entityType}/entities', [EntityController::class, 'index'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entities');
        Route::post('vaults/{vault}/entity-types/{entityType}/entities', [EntityController::class, 'create'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entities.create');

        Route::get('life-event-categories', [LifeEventCategoryController::class, 'index'])->name('lifeEventCategories');
        Route::get('life-event-categories/{lifeEventCategory}', [LifeEventCategoryController::class, 'show'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.show');
        Route::post('life-event-categories', [LifeEventCategoryController::class, 'create'])->name('lifeEventCategories.create');
        Route::put('life-event-categories/{lifeEventCategory}', [LifeEventCategoryController::class, 'update'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.update');
        Route::delete('life-event-categories/{lifeEventCategory}', [LifeEventCategoryController::class, 'destroy'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.destroy');

        Route::put('life-event-categories/{lifeEventCategory}/activation', [LifeEventCategoryActivationController::class, 'update'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.activation.update');
        Route::put('life-event-categories/{lifeEventCategory}/position', [LifeEventCategoryPositionController::class, 'update'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.position.update');
        Route::get('life-event-categories/{lifeEventCategory}/types', [LifeEventCategoryTypeController::class, 'index'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.types');

        Route::get('life-event-types', [LifeEventTypeController::class, 'index'])->name('lifeEventTypes');
        Route::get('life-event-types/{lifeEventType}', [LifeEventTypeController::class, 'show'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.show');
        Route::post('life-event-types', [LifeEventTypeController::class, 'create'])->name('lifeEventTypes.create');
        Route::put('life-event-types/{lifeEventType}', [LifeEventTypeController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.update');
        Route::delete('life-event-types/{lifeEventType}', [LifeEventTypeController::class, 'destroy'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.destroy');

        Route::put('life-event-types/{lifeEventType}/activation', [LifeEventTypeActivationController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.activation.update');
        Route::put('life-event-types/{lifeEventType}/position', [LifeEventTypePositionController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.position.update');

        Route::get('life-event-types/{lifeEventType}/fields', [LifeEventTypeFieldController::class, 'index'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.fields');
        Route::put('life-event-types/{lifeEventType}/fields', [LifeEventTypeFieldController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.fields.update');

        Route::get('relationship-categories', [RelationshipCategoryController::class, 'index'])->name('relationshipCategories');
        Route::get('relationship-categories/{relationshipCategory}', [RelationshipCategoryController::class, 'show'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.show');
        Route::get('relationship-categories/{relationshipCategory}/types', [RelationshipCategoryTypeController::class, 'index'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.types');
        Route::post('relationship-categories', [RelationshipCategoryController::class, 'create'])->name('relationshipCategories.create');
        Route::put('relationship-categories/{relationshipCategory}', [RelationshipCategoryController::class, 'update'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.update');
        Route::delete('relationship-categories/{relationshipCategory}', [RelationshipCategoryController::class, 'destroy'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.destroy');

        Route::put('relationship-categories/{relationshipCategory}/activation', [RelationshipCategoryActivationController::class, 'update'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.activation.update');
        Route::put('relationship-categories/{relationshipCategory}/position', [RelationshipCategoryPositionController::class, 'update'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.position.update');

        Route::get('relationship-types', [RelationshipTypeController::class, 'index'])->name('relationshipTypes');
        Route::get('relationship-types/{relationshipType}', [RelationshipTypeController::class, 'show'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.show');
        Route::post('relationship-types', [RelationshipTypeController::class, 'create'])->name('relationshipTypes.create');
        Route::put('relationship-types/{relationshipType}', [RelationshipTypeController::class, 'update'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.update');
        Route::delete('relationship-types/{relationshipType}', [RelationshipTypeController::class, 'destroy'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.destroy');

        Route::put('relationship-types/{relationshipType}/activation', [RelationshipTypeActivationController::class, 'update'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.activation.update');
        Route::put('relationship-types/{relationshipType}/position', [RelationshipTypePositionController::class, 'update'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.position.update');

        Route::get('templates', [TemplateController::class, 'index'])->name('templates');
        Route::get('templates/{template}', [TemplateController::class, 'show'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.show');
        Route::post('templates', [TemplateController::class, 'create'])->name('templates.create');
        Route::put('templates/{template}', [TemplateController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.update');
        Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.destroy');
        Route::put('templates/{template}/activation', [TemplateActivationController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.activation.update');
        Route::put('templates/{template}/position', [TemplatePositionController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.position.update');
        Route::post('templates/{template}/duplicate', [TemplateDuplicateController::class, 'create'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.duplicate.create');

        Route::get('templates/{template}/versions', [StructureVersionController::class, 'index'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.versions');
        Route::get('templates/{template}/versions/{version}', [StructureVersionController::class, 'show'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.show');

        Route::post('templates/{template}/versions', [StructureVersionController::class, 'create'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.versions.create');
        Route::put('templates/{template}/versions/{version}/publication', [StructureVersionPublicationController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.publication.update');
        Route::delete('templates/{template}/versions/{version}', [StructureVersionController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.destroy');

        Route::post('templates/{template}/versions/{version}/pages', [StructureVersionPageController::class, 'create'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.pages.create');
        Route::put('templates/{template}/versions/{version}/pages/{page}', [StructureVersionPageController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'page' => '[0-9a-fA-F-]{36}'])->name('templates.versions.pages.update');
        Route::delete('templates/{template}/versions/{version}/pages/{page}', [StructureVersionPageController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'page' => '[0-9a-fA-F-]{36}'])->name('templates.versions.pages.destroy');
        Route::put('templates/{template}/versions/{version}/pages/{page}/position', [StructureVersionPagePositionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'page' => '[0-9a-fA-F-]{36}'])->name('templates.versions.pages.position.update');

        Route::post('templates/{template}/versions/{version}/sections', [StructureVersionSectionController::class, 'create'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.sections.create');
        Route::put('templates/{template}/versions/{version}/sections/{section}', [StructureVersionSectionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.update');
        Route::delete('templates/{template}/versions/{version}/sections/{section}', [StructureVersionSectionController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.destroy');
        Route::put('templates/{template}/versions/{version}/sections/{section}/position', [StructureVersionSectionPositionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.position.update');
        Route::put('templates/{template}/versions/{version}/sections/{section}/page', [StructureVersionSectionPageController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.page.update');

        Route::post('templates/{template}/versions/{version}/sections/{section}/fields', [FieldController::class, 'create'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.create');
        Route::put('templates/{template}/versions/{version}/sections/{section}/fields/{field}', [FieldController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*', 'field' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.update');
        Route::delete('templates/{template}/versions/{version}/sections/{section}/fields/{field}', [FieldController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*', 'field' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.destroy');
        Route::put('templates/{template}/versions/{version}/sections/{section}/fields/{field}/position', [FieldPositionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*', 'field' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.position.update');

        Route::delete('vaults/{vault}/merges/{merge}', [ContactMergeController::class, 'destroy'])->where(['merge' => '[0-9a-fA-F-]{36}'])->name('merges.destroy');
        Route::get('vaults/{vault}/duplicates', [ContactDuplicateController::class, 'index'])->name('duplicates');
        Route::put('vaults/{vault}/duplicates/{candidate}/dismissal', [ContactDuplicateDismissalController::class, 'update'])->where(['candidate' => '[1-9][0-9]*'])->name('duplicates.dismissal.update');

        Route::get('vaults/{vault}/contact-list-columns', [ContactListColumnController::class, 'show'])->name('contactListColumns.show');
        Route::put('vaults/{vault}/contact-list-columns', [ContactListColumnController::class, 'update'])->name('contactListColumns.update');

        Route::get('vaults/{vault}/contacts', [ContactController::class, 'index'])->name('contacts');
        Route::get('vaults/{vault}/search', [SearchController::class, 'index'])->name('search');
        Route::get('vaults/{vault}/contacts/me', [ContactMeController::class, 'show'])->name('contacts.me');
        Route::get('vaults/{vault}/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::get('vaults/{vault}/contacts/{contact}/vcard', [ContactVCardController::class, 'show'])->name('contacts.vcard.show');
        Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->name('contacts.create');
        Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::get('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'show'])->name('contacts.merge.show');
        Route::post('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'create'])->name('contacts.merge.create');

        Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])->name('contacts.name.update');
        Route::get('vaults/{vault}/contacts/{contact}/name-representations', [ContactNameRepresentationController::class, 'index'])->name('contacts.nameRepresentations');
        Route::put('vaults/{vault}/contacts/{contact}/name-representations', [ContactNameRepresentationController::class, 'update'])->name('contacts.nameRepresentations.update');

        Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->name('contacts.favourite.update');
        Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])->name('contacts.avatar.update');
        Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->name('contacts.avatar.destroy');
        Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactStructureVersionController::class, 'update'])->name('contacts.templateVersion.update');

        Route::get('vaults/{vault}/contacts/{contact}/reminders', [ContactReminderController::class, 'index'])->name('contacts.reminders');
        Route::post('vaults/{vault}/contacts/{contact}/reminders', [ContactReminderController::class, 'create'])->name('contacts.reminders.create');
        Route::get('vaults/{vault}/contacts/{contact}/reminders/{reminder}', [ContactReminderController::class, 'show'])
            ->where('reminder', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminders.show');
        Route::put('vaults/{vault}/contacts/{contact}/reminders/{reminder}', [ContactReminderController::class, 'update'])
            ->where('reminder', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminders.update');
        Route::delete('vaults/{vault}/contacts/{contact}/reminders/{reminder}', [ContactReminderController::class, 'destroy'])
            ->where('reminder', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminders.destroy');
        Route::get('vaults/{vault}/contacts/{contact}/reminder-occurrences', [ContactReminderOccurrenceController::class, 'index'])->name('contacts.reminderOccurrences');
        Route::put('vaults/{vault}/contacts/{contact}/reminder-occurrences/{occurrence}/answer', [ReminderOccurrenceAnswerController::class, 'update'])
            ->where('occurrence', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminderOccurrences.answer.update');
    });
});
