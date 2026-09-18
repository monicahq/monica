<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ActivityCategoryActivationController;
use App\Http\Controllers\Api\ActivityCategoryController;
use App\Http\Controllers\Api\ActivityCategoryPositionController;
use App\Http\Controllers\Api\ActivityCategoryTypeController;
use App\Http\Controllers\Api\ActivityTypeActivationController;
use App\Http\Controllers\Api\ActivityTypeController;
use App\Http\Controllers\Api\ActivityTypeFieldController;
use App\Http\Controllers\Api\ActivityTypePositionController;
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
use App\Http\Controllers\Api\ContactMeController;
use App\Http\Controllers\Api\ContactMergeController;
use App\Http\Controllers\Api\ContactNameController;
use App\Http\Controllers\Api\ContactStructureVersionController;
use App\Http\Controllers\Api\ContactVCardController;
use App\Http\Controllers\Api\EmailSentController;
use App\Http\Controllers\Api\EntityTypeActivationController;
use App\Http\Controllers\Api\EntityTypeController;
use App\Http\Controllers\Api\EntityTypePositionController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldPositionController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeTimezoneController;
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
use App\Http\Middleware\SetMcpLogSource;
use App\Mcp\Servers\EricaServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('status', [StatusController::class, 'show'])->middleware('throttle:60,1')->name('status');

    Route::post('register', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('register');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');
        Route::put('me/timezone', [MeTimezoneController::class, 'update'])->name('me.timezone.update');

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

        Route::get('activity-categories', [ActivityCategoryController::class, 'index'])->name('activityCategories');
        Route::get('activity-categories/{activityCategory}', [ActivityCategoryController::class, 'show'])->where('activityCategory', '[0-9a-fA-F-]{36}')->name('activityCategories.show');
        Route::post('activity-categories', [ActivityCategoryController::class, 'create'])->name('activityCategories.create');
        Route::put('activity-categories/{activityCategory}', [ActivityCategoryController::class, 'update'])->where('activityCategory', '[0-9a-fA-F-]{36}')->name('activityCategories.update');
        Route::delete('activity-categories/{activityCategory}', [ActivityCategoryController::class, 'destroy'])->where('activityCategory', '[0-9a-fA-F-]{36}')->name('activityCategories.destroy');

        Route::put('activity-categories/{activityCategory}/activation', [ActivityCategoryActivationController::class, 'update'])->where('activityCategory', '[0-9a-fA-F-]{36}')->name('activityCategories.activation.update');
        Route::put('activity-categories/{activityCategory}/position', [ActivityCategoryPositionController::class, 'update'])->where('activityCategory', '[0-9a-fA-F-]{36}')->name('activityCategories.position.update');
        Route::get('activity-categories/{activityCategory}/types', [ActivityCategoryTypeController::class, 'index'])->where('activityCategory', '[0-9a-fA-F-]{36}')->name('activityCategories.types');

        Route::get('activity-types', [ActivityTypeController::class, 'index'])->name('activityTypes');
        Route::get('activity-types/{activityType}', [ActivityTypeController::class, 'show'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.show');
        Route::post('activity-types', [ActivityTypeController::class, 'create'])->name('activityTypes.create');
        Route::put('activity-types/{activityType}', [ActivityTypeController::class, 'update'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.update');
        Route::delete('activity-types/{activityType}', [ActivityTypeController::class, 'destroy'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.destroy');

        Route::put('activity-types/{activityType}/activation', [ActivityTypeActivationController::class, 'update'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.activation.update');
        Route::put('activity-types/{activityType}/position', [ActivityTypePositionController::class, 'update'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.position.update');

        Route::get('activity-types/{activityType}/fields', [ActivityTypeFieldController::class, 'index'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.fields');
        Route::put('activity-types/{activityType}/fields', [ActivityTypeFieldController::class, 'update'])->where('activityType', '[0-9a-fA-F-]{36}')->name('activityTypes.fields.update');

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

        Route::get('vaults/{vault}/contacts', [ContactController::class, 'index'])->name('contacts');
        Route::get('vaults/{vault}/contacts/me', [ContactMeController::class, 'show'])->name('contacts.me');
        Route::get('vaults/{vault}/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::get('vaults/{vault}/contacts/{contact}/vcard', [ContactVCardController::class, 'show'])->name('contacts.vcard.show');
        Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->name('contacts.create');
        Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::get('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'show'])->name('contacts.merge.show');
        Route::post('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'create'])->name('contacts.merge.create');

        Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])->name('contacts.name.update');

        Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->name('contacts.favourite.update');
        Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])->name('contacts.avatar.update');
        Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->name('contacts.avatar.destroy');
        Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactStructureVersionController::class, 'update'])->name('contacts.templateVersion.update');
    });
});

Mcp::web('mcp', EricaServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp', SetMcpLogSource::class])
    ->withoutMiddleware('throttle:api');
