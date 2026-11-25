<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AccountExportController;
use App\Http\Controllers\Api\AccountExportDownloadController;
use App\Http\Controllers\Api\AccountImportContactController;
use App\Http\Controllers\Api\AccountImportController;
use App\Http\Controllers\Api\AccountImportReportController;
use App\Http\Controllers\Api\Admin\AgentFeedbackController as AdminAgentFeedbackController;
use App\Http\Controllers\Api\Admin\AgentFeedbackNoteController as AdminAgentFeedbackNoteController;
use App\Http\Controllers\Api\Admin\AgentFeedbackStatusController as AdminAgentFeedbackStatusController;
use App\Http\Controllers\Api\Admin\SupportNoteController as AdminSupportNoteController;
use App\Http\Controllers\Api\Admin\SupportReplyController as AdminSupportReplyController;
use App\Http\Controllers\Api\Admin\SupportRequestController as AdminSupportRequestController;
use App\Http\Controllers\Api\Admin\SupportStatusController as AdminSupportStatusController;
use App\Http\Controllers\Api\AgentFeedbackController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ChangelogCommentController;
use App\Http\Controllers\Api\ChangelogEntryController;
use App\Http\Controllers\Api\ChangelogSeenController;
use App\Http\Controllers\Api\ChangelogVoteController;
use App\Http\Controllers\Api\ChannelProofController as ApiChannelProofController;
use App\Http\Controllers\Api\ChannelSetupController as ApiChannelSetupController;
use App\Http\Controllers\Api\ChannelTestController as ApiChannelTestController;
use App\Http\Controllers\Api\CheckoutSessionController;
use App\Http\Controllers\Api\ContactAvatarController;
use App\Http\Controllers\Api\ContactCategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactDeletionController;
use App\Http\Controllers\Api\ContactDuplicateController;
use App\Http\Controllers\Api\ContactDuplicateDismissalController;
use App\Http\Controllers\Api\ContactEntityController;
use App\Http\Controllers\Api\ContactFavouriteController;
use App\Http\Controllers\Api\ContactGroupController;
use App\Http\Controllers\Api\ContactListColumnController;
use App\Http\Controllers\Api\ContactMarkdownController;
use App\Http\Controllers\Api\ContactMeController;
use App\Http\Controllers\Api\ContactMergeController;
use App\Http\Controllers\Api\ContactNameController;
use App\Http\Controllers\Api\ContactNameRepresentationController;
use App\Http\Controllers\Api\ContactPartialController;
use App\Http\Controllers\Api\ContactRelationshipController;
use App\Http\Controllers\Api\ContactReminderController;
use App\Http\Controllers\Api\ContactReminderOccurrenceController;
use App\Http\Controllers\Api\ContactSectionController;
use App\Http\Controllers\Api\ContactStructureVersionController;
use App\Http\Controllers\Api\ContactSummaryController;
use App\Http\Controllers\Api\ContactTemplateController;
use App\Http\Controllers\Api\ContactTemplatePreviewController;
use App\Http\Controllers\Api\ContactVCardController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\EmailSentController;
use App\Http\Controllers\Api\EntityController;
use App\Http\Controllers\Api\EntityTypeActivationController;
use App\Http\Controllers\Api\EntityTypeController;
use App\Http\Controllers\Api\EntityTypePositionController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldPositionController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupFileContentController;
use App\Http\Controllers\Api\GroupFileController;
use App\Http\Controllers\Api\GroupLifeEventController;
use App\Http\Controllers\Api\GroupMemberController;
use App\Http\Controllers\Api\GroupNoteController;
use App\Http\Controllers\Api\GroupReminderController;
use App\Http\Controllers\Api\GroupReminderOccurrenceAnswerController;
use App\Http\Controllers\Api\GroupReminderOccurrenceController;
use App\Http\Controllers\Api\GroupRestorationController;
use App\Http\Controllers\Api\Knowledge\KnowledgeFolderController;
use App\Http\Controllers\Api\Knowledge\KnowledgeListColumnController;
use App\Http\Controllers\Api\Knowledge\KnowledgeListController;
use App\Http\Controllers\Api\Knowledge\KnowledgeListItemController;
use App\Http\Controllers\Api\Knowledge\KnowledgeNoteController;
use App\Http\Controllers\Api\Knowledge\KnowledgeSearchController;
use App\Http\Controllers\Api\LifeEventCategoryActivationController;
use App\Http\Controllers\Api\LifeEventCategoryController;
use App\Http\Controllers\Api\LifeEventCategoryPositionController;
use App\Http\Controllers\Api\LifeEventCategoryTypeController;
use App\Http\Controllers\Api\LifeEventController;
use App\Http\Controllers\Api\LifeEventRestorationController;
use App\Http\Controllers\Api\LifeEventSliceController;
use App\Http\Controllers\Api\LifeEventTypeActivationController;
use App\Http\Controllers\Api\LifeEventTypeController;
use App\Http\Controllers\Api\LifeEventTypeFieldController;
use App\Http\Controllers\Api\LifeEventTypePositionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\McpCallController;
use App\Http\Controllers\Api\MeContactListViewController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeFullAccessOfferController;
use App\Http\Controllers\Api\MeTimezoneController;
use App\Http\Controllers\Api\MeVaultListController;
use App\Http\Controllers\Api\MeWelcomeController;
use App\Http\Controllers\Api\NotificationChannelController as ApiNotificationChannelController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\NotificationPreferenceController as ApiNotificationPreferenceController;
use App\Http\Controllers\Api\NotificationReadController as ApiNotificationReadController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PortalSessionController;
use App\Http\Controllers\Api\PossessionConditions\PossessionConditionController;
use App\Http\Controllers\Api\PossessionConditions\PossessionConditionPositionController;
use App\Http\Controllers\Api\Possessions\PossessionController;
use App\Http\Controllers\Api\Possessions\PossessionFieldValueController;
use App\Http\Controllers\Api\Possessions\PossessionInsuranceRecordController;
use App\Http\Controllers\Api\Possessions\PossessionLoanController;
use App\Http\Controllers\Api\Possessions\PossessionLocationStayController;
use App\Http\Controllers\Api\Possessions\PossessionMaintenanceRecordController;
use App\Http\Controllers\Api\Possessions\PossessionPositionController;
use App\Http\Controllers\Api\Possessions\PossessionProvenanceEventController;
use App\Http\Controllers\Api\Possessions\PossessionTimelineController;
use App\Http\Controllers\Api\Possessions\PossessionTransactionController;
use App\Http\Controllers\Api\Possessions\PossessionTransactionDocumentController;
use App\Http\Controllers\Api\Possessions\PossessionValuationController;
use App\Http\Controllers\Api\Possessions\PossessionValuationDocumentController;
use App\Http\Controllers\Api\PossessionTags\PossessionTagController;
use App\Http\Controllers\Api\PossessionTypes\PossessionFieldController;
use App\Http\Controllers\Api\PossessionTypes\PossessionFieldGroupController;
use App\Http\Controllers\Api\PossessionTypes\PossessionFieldGroupPositionController;
use App\Http\Controllers\Api\PossessionTypes\PossessionFieldPositionController;
use App\Http\Controllers\Api\PossessionTypes\PossessionTypeController;
use App\Http\Controllers\Api\PossessionTypes\PossessionTypeExportController;
use App\Http\Controllers\Api\PossessionTypes\PossessionTypeImportController;
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
use App\Http\Controllers\Api\SupportAttachmentContentController;
use App\Http\Controllers\Api\SupportMessageController;
use App\Http\Controllers\Api\SupportRequestController;
use App\Http\Controllers\Api\SupportSolutionController;
use App\Http\Controllers\Api\TemplateActivationController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TemplateDuplicateController;
use App\Http\Controllers\Api\TemplatePositionController;
use App\Http\Controllers\Api\ValueListController;
use App\Http\Controllers\Api\ValueListItemActivationController;
use App\Http\Controllers\Api\ValueListItemController;
use App\Http\Controllers\Api\ValueListItemPositionController;
use App\Http\Controllers\Api\VaultController;
use App\Http\Controllers\Api\VaultReminderController;
use App\Http\Controllers\Api\VaultReminderOccurrenceController;
use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Http\Middleware\Sample;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::match(['GET', 'QUERY'], 'status', [StatusController::class, 'show'])->middleware(['throttle:60,1', Sample::rate(0.01)])->name('status');

    Route::post('register', [RegistrationController::class, 'store'])->middleware(['registration', 'throttle:6,1'])->name('register');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

    Route::middleware(['auth:sanctum', 'query.body'])->group(function (): void {
        Route::delete('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::match(['GET', 'QUERY'], 'me', [MeController::class, 'show'])->name('me');
        Route::put('me', [MeController::class, 'update'])->name('me.update');
        Route::put('me/timezone', [MeTimezoneController::class, 'update'])->name('me.timezone.update');
        Route::put('me/vault-list', [MeVaultListController::class, 'update'])->name('me.vaultList.update');
        Route::put('me/contact-list-view', [MeContactListViewController::class, 'update'])->name('me.contactListView.update');
        Route::put('me/welcome', [MeWelcomeController::class, 'update'])->name('me.welcome.update');
        Route::put('me/full-access-offer', [MeFullAccessOfferController::class, 'update'])->name('me.fullAccessOffer.update');

        // What's new, switched on per instance
        Route::middleware(['changelog'])->group(function (): void {
            Route::match(['GET', 'QUERY'], 'changelog-entries', [ChangelogEntryController::class, 'index'])->name('changelogEntries');
            Route::put('changelog-entries/seen', [ChangelogSeenController::class, 'update'])->name('changelogEntries.seen.update');
            Route::put('changelog-entries/{entry}/vote', [ChangelogVoteController::class, 'update'])->where('entry', '[0-7][0-9A-HJKMNP-TV-Z]{25}')->name('changelogEntries.vote.update');
            Route::delete('changelog-entries/{entry}/vote', [ChangelogVoteController::class, 'destroy'])->where('entry', '[0-7][0-9A-HJKMNP-TV-Z]{25}')->name('changelogEntries.vote.destroy');
            Route::match(['GET', 'QUERY'], 'changelog-entries/{entry}/comments', [ChangelogCommentController::class, 'index'])->where('entry', '[0-7][0-9A-HJKMNP-TV-Z]{25}')->name('changelogEntries.comments');
            Route::post('changelog-entries/{entry}/comments', [ChangelogCommentController::class, 'create'])->middleware('throttle:10,1')->where('entry', '[0-7][0-9A-HJKMNP-TV-Z]{25}')->name('changelogEntries.comments.create');
        });

        Route::match(['GET', 'QUERY'], 'billing', [BillingController::class, 'show'])->name('billing');
        Route::match(['GET', 'QUERY'], 'billing/payments', [PaymentController::class, 'index'])->name('billing.payments');
        Route::match(['GET', 'QUERY'], 'billing/payments/{payment}', [PaymentController::class, 'show'])->where('payment', '[A-Za-z0-9_]+')->name('billing.payments.show');
        Route::post('billing/checkout-sessions', [CheckoutSessionController::class, 'store'])->name('billing.checkoutSessions.create');
        Route::put('billing/subscription', [SubscriptionController::class, 'update'])->name('billing.subscription.update');
        Route::delete('billing/subscription', [SubscriptionController::class, 'destroy'])->name('billing.subscription.destroy');
        Route::delete('billing/subscription/cancellation', [SubscriptionCancellationController::class, 'destroy'])->name('billing.subscription.cancellation.destroy');
        Route::post('billing/portal-sessions', [PortalSessionController::class, 'store'])->name('billing.portalSessions.create');

        Route::middleware(['support'])->group(function (): void {
            Route::match(['GET', 'QUERY'], 'support-requests', [SupportRequestController::class, 'index'])->name('supportRequests');
            Route::post('support-requests', [SupportRequestController::class, 'create'])->middleware('throttle:support')->name('supportRequests.create');
            Route::match(['GET', 'QUERY'], 'support-requests/{supportRequest}', [SupportRequestController::class, 'show'])->where('supportRequest', '[0-9a-fA-F-]{36}')->name('supportRequests.show');
            Route::post('support-requests/{supportRequest}/messages', [SupportMessageController::class, 'create'])->middleware('throttle:support')->where('supportRequest', '[0-9a-fA-F-]{36}')->name('supportRequests.messages.create');
            Route::post('support-requests/{supportRequest}/solution', [SupportSolutionController::class, 'create'])->where('supportRequest', '[0-9a-fA-F-]{36}')->name('supportRequests.solution.create');
            Route::match(['GET', 'QUERY'], 'support-requests/{supportRequest}/attachments/{attachment}/content', [SupportAttachmentContentController::class, 'show'])->where(['supportRequest' => '[0-9a-fA-F-]{36}', 'attachment' => '[0-9a-fA-F-]{36}'])->name('supportRequests.attachments.content.show');
        });

        Route::middleware(['support', 'administrator', 'administration'])->group(function (): void {
            Route::match(['GET', 'QUERY'], 'admin/support-requests', [AdminSupportRequestController::class, 'index'])->name('admin.supportRequests');
            Route::match(['GET', 'QUERY'], 'admin/support-requests/{supportRequest}', [AdminSupportRequestController::class, 'show'])->where('supportRequest', '[0-9a-fA-F-]{36}')->name('admin.supportRequests.show');
            Route::post('admin/support-requests/{supportRequest}/replies', [AdminSupportReplyController::class, 'create'])->where('supportRequest', '[0-9a-fA-F-]{36}')->name('admin.supportRequests.replies.create');
            Route::post('admin/support-requests/{supportRequest}/notes', [AdminSupportNoteController::class, 'create'])->where('supportRequest', '[0-9a-fA-F-]{36}')->name('admin.supportRequests.notes.create');
            Route::put('admin/support-requests/{supportRequest}/status', [AdminSupportStatusController::class, 'update'])->where('supportRequest', '[0-9a-fA-F-]{36}')->name('admin.supportRequests.status.update');
        });

        Route::post('agent-feedback', [AgentFeedbackController::class, 'create'])->middleware('throttle:10,1')->name('agentFeedback.create');

        Route::middleware(['administrator', 'administration'])->group(function (): void {
            Route::match(['GET', 'QUERY'], 'admin/agent-feedback', [AdminAgentFeedbackController::class, 'index'])->name('admin.agentFeedback');
            Route::match(['GET', 'QUERY'], 'admin/agent-feedback/{agentFeedbackReport}', [AdminAgentFeedbackController::class, 'show'])->whereUuid('agentFeedbackReport')->name('admin.agentFeedback.show');
            Route::post('admin/agent-feedback/{agentFeedbackReport}/notes', [AdminAgentFeedbackNoteController::class, 'create'])->whereUuid('agentFeedbackReport')->name('admin.agentFeedback.notes.create');
            Route::put('admin/agent-feedback/{agentFeedbackReport}/status', [AdminAgentFeedbackStatusController::class, 'update'])->whereUuid('agentFeedbackReport')->name('admin.agentFeedback.status.update');
        });

        Route::match(['GET', 'QUERY'], 'api-keys', [ApiKeyController::class, 'index'])->name('apiKeys');
        Route::match(['GET', 'QUERY'], 'api-keys/{apiKey}', [ApiKeyController::class, 'show'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.show');
        Route::post('api-keys', [ApiKeyController::class, 'create'])->name('apiKeys.create');
        Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->where('apiKey', '[1-9][0-9]*')->name('apiKeys.destroy');

        Route::match(['GET', 'QUERY'], 'notifications', [ApiNotificationController::class, 'index'])->name('notifications');
        Route::put('notifications/read', [ApiNotificationReadController::class, 'updateAll'])->name('notifications.readAll.update');
        Route::match(['GET', 'QUERY'], 'notifications/{notification}', [ApiNotificationController::class, 'show'])->where('notification', '[0-9a-fA-F-]{36}')->name('notifications.show');
        Route::put('notifications/{notification}/read', [ApiNotificationReadController::class, 'update'])->where('notification', '[0-9a-fA-F-]{36}')->name('notifications.read.update');

        Route::match(['GET', 'QUERY'], 'notification-channels', [ApiNotificationChannelController::class, 'index'])->name('notificationChannels');
        Route::match(['GET', 'QUERY'], 'notification-channels/{channel}', [ApiNotificationChannelController::class, 'show'])->name('notificationChannels.show');
        Route::put('notification-channels/{channel}', [ApiNotificationChannelController::class, 'update'])->name('notificationChannels.update');
        Route::post('notification-channels/{channel}/proof', [ApiChannelProofController::class, 'create'])->name('notificationChannels.proof.store');
        Route::put('notification-channels/{channel}/proof', [ApiChannelProofController::class, 'update'])->name('notificationChannels.proof.update');
        Route::post('notification-channels/{channel}/test', [ApiChannelTestController::class, 'create'])->name('notificationChannels.test.store');
        Route::delete('notification-channels/{channel}/setup', [ApiChannelSetupController::class, 'destroy'])->name('notificationChannels.setup.destroy');

        Route::match(['GET', 'QUERY'], 'notification-preferences', [ApiNotificationPreferenceController::class, 'show'])->name('notificationPreferences.show');
        Route::put('notification-preferences', [ApiNotificationPreferenceController::class, 'update'])->name('notificationPreferences.update');

        Route::match(['GET', 'QUERY'], 'logs', [LogController::class, 'index'])->name('logs');
        Route::match(['GET', 'QUERY'], 'logs/{log}', [LogController::class, 'show'])->where('log', '[1-9][0-9]*')->name('logs.show');

        Route::match(['GET', 'QUERY'], 'mcp-calls', [McpCallController::class, 'index'])->middleware('flag:mcp-server')->name('mcpCalls');
        Route::match(['GET', 'QUERY'], 'mcp-calls/{mcpCall}', [McpCallController::class, 'show'])->middleware('flag:mcp-server')->where('mcpCall', '[0-9a-fA-F-]{36}')->name('mcpCalls.show');

        Route::match(['GET', 'QUERY'], 'emails-sent', [EmailSentController::class, 'index'])->name('emailsSent');
        Route::match(['GET', 'QUERY'], 'emails-sent/{emailSent}', [EmailSentController::class, 'show'])->where('emailSent', '[1-9][0-9]*')->name('emailsSent.show');

        Route::match(['GET', 'QUERY'], 'vaults', [VaultController::class, 'index'])->name('vaults');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}', [VaultController::class, 'show'])->name('vaults.show');
        Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
        Route::put('vaults/{vault}', [VaultController::class, 'update'])->name('vaults.update');
        Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->name('vaults.destroy');

        Route::match(['GET', 'QUERY'], 'entity-types', [EntityTypeController::class, 'index'])->name('entityTypes');
        Route::match(['GET', 'QUERY'], 'entity-types/{entityType}', [EntityTypeController::class, 'show'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.show');
        Route::post('entity-types', [EntityTypeController::class, 'create'])->name('entityTypes.create');
        Route::put('entity-types/{entityType}', [EntityTypeController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.update');
        Route::delete('entity-types/{entityType}', [EntityTypeController::class, 'destroy'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.destroy');

        Route::put('entity-types/{entityType}/activation', [EntityTypeActivationController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.activation.update');
        Route::put('entity-types/{entityType}/position', [EntityTypePositionController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.position.update');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/entity-types/{entityType}/entities', [EntityController::class, 'index'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entities');
        Route::post('vaults/{vault}/entity-types/{entityType}/entities', [EntityController::class, 'create'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entities.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/entities/{entity}', [EntityController::class, 'show'])->where('entity', '[0-9a-fA-F-]{36}')->name('entities.show');
        Route::put('vaults/{vault}/entities/{entity}', [EntityController::class, 'update'])->where('entity', '[0-9a-fA-F-]{36}')->name('entities.update');

        Route::match(['GET', 'QUERY'], 'life-event-categories', [LifeEventCategoryController::class, 'index'])->name('lifeEventCategories');
        Route::match(['GET', 'QUERY'], 'life-event-categories/{lifeEventCategory}', [LifeEventCategoryController::class, 'show'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.show');
        Route::post('life-event-categories', [LifeEventCategoryController::class, 'create'])->name('lifeEventCategories.create');
        Route::put('life-event-categories/{lifeEventCategory}', [LifeEventCategoryController::class, 'update'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.update');
        Route::delete('life-event-categories/{lifeEventCategory}', [LifeEventCategoryController::class, 'destroy'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.destroy');

        Route::put('life-event-categories/{lifeEventCategory}/activation', [LifeEventCategoryActivationController::class, 'update'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.activation.update');
        Route::put('life-event-categories/{lifeEventCategory}/position', [LifeEventCategoryPositionController::class, 'update'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.position.update');
        Route::match(['GET', 'QUERY'], 'life-event-categories/{lifeEventCategory}/types', [LifeEventCategoryTypeController::class, 'index'])->where('lifeEventCategory', '[0-9a-fA-F-]{36}')->name('lifeEventCategories.types');

        Route::match(['GET', 'QUERY'], 'life-event-types', [LifeEventTypeController::class, 'index'])->name('lifeEventTypes');
        Route::match(['GET', 'QUERY'], 'life-event-types/{lifeEventType}', [LifeEventTypeController::class, 'show'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.show');
        Route::post('life-event-types', [LifeEventTypeController::class, 'create'])->name('lifeEventTypes.create');
        Route::put('life-event-types/{lifeEventType}', [LifeEventTypeController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.update');
        Route::delete('life-event-types/{lifeEventType}', [LifeEventTypeController::class, 'destroy'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.destroy');

        Route::put('life-event-types/{lifeEventType}/activation', [LifeEventTypeActivationController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.activation.update');
        Route::put('life-event-types/{lifeEventType}/position', [LifeEventTypePositionController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.position.update');

        Route::match(['GET', 'QUERY'], 'life-event-types/{lifeEventType}/fields', [LifeEventTypeFieldController::class, 'index'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.fields');
        Route::put('life-event-types/{lifeEventType}/fields', [LifeEventTypeFieldController::class, 'update'])->where('lifeEventType', '[0-9a-fA-F-]{36}')->name('lifeEventTypes.fields.update');

        Route::match(['GET', 'QUERY'], 'relationship-categories', [RelationshipCategoryController::class, 'index'])->name('relationshipCategories');
        Route::match(['GET', 'QUERY'], 'relationship-categories/{relationshipCategory}', [RelationshipCategoryController::class, 'show'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.show');
        Route::match(['GET', 'QUERY'], 'relationship-categories/{relationshipCategory}/types', [RelationshipCategoryTypeController::class, 'index'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.types');
        Route::post('relationship-categories', [RelationshipCategoryController::class, 'create'])->name('relationshipCategories.create');
        Route::put('relationship-categories/{relationshipCategory}', [RelationshipCategoryController::class, 'update'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.update');
        Route::delete('relationship-categories/{relationshipCategory}', [RelationshipCategoryController::class, 'destroy'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.destroy');

        Route::put('relationship-categories/{relationshipCategory}/activation', [RelationshipCategoryActivationController::class, 'update'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.activation.update');
        Route::put('relationship-categories/{relationshipCategory}/position', [RelationshipCategoryPositionController::class, 'update'])->where('relationshipCategory', '[0-9a-fA-F-]{36}')->name('relationshipCategories.position.update');

        Route::match(['GET', 'QUERY'], 'locations', [LocationController::class, 'index'])->name('locations');
        Route::match(['GET', 'QUERY'], 'locations/{location}', [LocationController::class, 'show'])->where('location', '[0-9a-fA-F-]{36}')->name('locations.show');
        Route::post('locations', [LocationController::class, 'create'])->name('locations.create');
        Route::put('locations/{location}', [LocationController::class, 'update'])->where('location', '[0-9a-fA-F-]{36}')->name('locations.update');
        Route::delete('locations/{location}', [LocationController::class, 'destroy'])->where('location', '[0-9a-fA-F-]{36}')->name('locations.destroy');

        Route::match(['GET', 'QUERY'], 'possession-types', [PossessionTypeController::class, 'index'])->name('possessionTypes');
        Route::post('possession-types', [PossessionTypeController::class, 'create'])->name('possessionTypes.create');
        Route::post('possession-types/import', [PossessionTypeImportController::class, 'create'])->name('possessionTypes.import');
        Route::match(['GET', 'QUERY'], 'possession-types/{possessionType}', [PossessionTypeController::class, 'show'])->where('possessionType', '[0-9a-fA-F-]{36}')->name('possessionTypes.show');
        Route::put('possession-types/{possessionType}', [PossessionTypeController::class, 'update'])->where('possessionType', '[0-9a-fA-F-]{36}')->name('possessionTypes.update');
        Route::delete('possession-types/{possessionType}', [PossessionTypeController::class, 'destroy'])->where('possessionType', '[0-9a-fA-F-]{36}')->name('possessionTypes.destroy');
        Route::match(['GET', 'QUERY'], 'possession-types/{possessionType}/export', [PossessionTypeExportController::class, 'show'])->where('possessionType', '[0-9a-fA-F-]{36}')->name('possessionTypes.export');
        Route::post('possession-types/{possessionType}/groups', [PossessionFieldGroupController::class, 'create'])->where('possessionType', '[0-9a-fA-F-]{36}')->name('possessionFieldGroups.create');
        Route::put('possession-types/{possessionType}/groups/{group}', [PossessionFieldGroupController::class, 'update'])->where(['possessionType' => '[0-9a-fA-F-]{36}', 'group' => '[0-9a-fA-F-]{36}'])->name('possessionFieldGroups.update');
        Route::delete('possession-types/{possessionType}/groups/{group}', [PossessionFieldGroupController::class, 'destroy'])->where(['possessionType' => '[0-9a-fA-F-]{36}', 'group' => '[0-9a-fA-F-]{36}'])->name('possessionFieldGroups.destroy');
        Route::put('possession-types/{possessionType}/groups/{group}/position', [PossessionFieldGroupPositionController::class, 'update'])->where(['possessionType' => '[0-9a-fA-F-]{36}', 'group' => '[0-9a-fA-F-]{36}'])->name('possessionFieldGroups.position');
        Route::post('possession-types/{possessionType}/fields', [PossessionFieldController::class, 'create'])->where('possessionType', '[0-9a-fA-F-]{36}')->name('possessionFields.create');
        Route::put('possession-types/{possessionType}/fields/{field}', [PossessionFieldController::class, 'update'])->where(['possessionType' => '[0-9a-fA-F-]{36}', 'field' => '[0-9a-fA-F-]{36}'])->name('possessionFields.update');
        Route::delete('possession-types/{possessionType}/fields/{field}', [PossessionFieldController::class, 'destroy'])->where(['possessionType' => '[0-9a-fA-F-]{36}', 'field' => '[0-9a-fA-F-]{36}'])->name('possessionFields.destroy');
        Route::put('possession-types/{possessionType}/fields/{field}/position', [PossessionFieldPositionController::class, 'update'])->where(['possessionType' => '[0-9a-fA-F-]{36}', 'field' => '[0-9a-fA-F-]{36}'])->name('possessionFields.position');

        Route::match(['GET', 'QUERY'], 'possession-tags', [PossessionTagController::class, 'index'])->name('possessionTags');
        Route::match(['GET', 'QUERY'], 'possession-tags/{possessionTag}', [PossessionTagController::class, 'show'])->where('possessionTag', '[0-9a-fA-F-]{36}')->name('possessionTags.show');
        Route::post('possession-tags', [PossessionTagController::class, 'create'])->name('possessionTags.create');
        Route::put('possession-tags/{possessionTag}', [PossessionTagController::class, 'update'])->where('possessionTag', '[0-9a-fA-F-]{36}')->name('possessionTags.update');
        Route::delete('possession-tags/{possessionTag}', [PossessionTagController::class, 'destroy'])->where('possessionTag', '[0-9a-fA-F-]{36}')->name('possessionTags.destroy');

        Route::match(['GET', 'QUERY'], 'possession-conditions', [PossessionConditionController::class, 'index'])->name('possessionConditions');
        Route::match(['GET', 'QUERY'], 'possession-conditions/{possessionCondition}', [PossessionConditionController::class, 'show'])->where('possessionCondition', '[0-9a-fA-F-]{36}')->name('possessionConditions.show');
        Route::post('possession-conditions', [PossessionConditionController::class, 'create'])->name('possessionConditions.create');
        Route::put('possession-conditions/{possessionCondition}', [PossessionConditionController::class, 'update'])->where('possessionCondition', '[0-9a-fA-F-]{36}')->name('possessionConditions.update');
        Route::put('possession-conditions/{possessionCondition}/position', [PossessionConditionPositionController::class, 'update'])->where('possessionCondition', '[0-9a-fA-F-]{36}')->name('possessionConditions.position');
        Route::delete('possession-conditions/{possessionCondition}', [PossessionConditionController::class, 'destroy'])->where('possessionCondition', '[0-9a-fA-F-]{36}')->name('possessionConditions.destroy');

        Route::match(['GET', 'QUERY'], 'relationship-types', [RelationshipTypeController::class, 'index'])->name('relationshipTypes');
        Route::match(['GET', 'QUERY'], 'relationship-types/{relationshipType}', [RelationshipTypeController::class, 'show'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.show');
        Route::post('relationship-types', [RelationshipTypeController::class, 'create'])->name('relationshipTypes.create');
        Route::put('relationship-types/{relationshipType}', [RelationshipTypeController::class, 'update'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.update');
        Route::delete('relationship-types/{relationshipType}', [RelationshipTypeController::class, 'destroy'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.destroy');

        Route::put('relationship-types/{relationshipType}/activation', [RelationshipTypeActivationController::class, 'update'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.activation.update');
        Route::put('relationship-types/{relationshipType}/position', [RelationshipTypePositionController::class, 'update'])->where('relationshipType', '[0-9a-fA-F-]{36}')->name('relationshipTypes.position.update');

        Route::match(['GET', 'QUERY'], 'countries', [CountryController::class, 'index'])->name('countries');
        Route::match(['GET', 'QUERY'], 'value-lists', [ValueListController::class, 'index'])->name('valueLists');
        Route::post('value-lists', [ValueListController::class, 'create'])->name('valueLists.create');
        Route::put('value-lists/{valueList}', [ValueListController::class, 'update'])->where('valueList', '[1-9][0-9]*')->name('valueLists.update');
        Route::delete('value-lists/{valueList}', [ValueListController::class, 'destroy'])->where('valueList', '[1-9][0-9]*')->name('valueLists.destroy');
        Route::post('value-lists/{valueList}/items', [ValueListItemController::class, 'create'])->where('valueList', '[1-9][0-9]*')->name('valueLists.items.create');
        Route::put('value-lists/{valueList}/items/{valueListItem}', [ValueListItemController::class, 'update'])->where(['valueList' => '[1-9][0-9]*', 'valueListItem' => '[1-9][0-9]*'])->name('valueLists.items.update');
        Route::delete('value-lists/{valueList}/items/{valueListItem}', [ValueListItemController::class, 'destroy'])->where(['valueList' => '[1-9][0-9]*', 'valueListItem' => '[1-9][0-9]*'])->name('valueLists.items.destroy');
        Route::put('value-lists/{valueList}/items/{valueListItem}/activation', [ValueListItemActivationController::class, 'update'])->where(['valueList' => '[1-9][0-9]*', 'valueListItem' => '[1-9][0-9]*'])->name('valueLists.items.activation.update');
        Route::put('value-lists/{valueList}/items/{valueListItem}/position', [ValueListItemPositionController::class, 'update'])->where(['valueList' => '[1-9][0-9]*', 'valueListItem' => '[1-9][0-9]*'])->name('valueLists.items.position.update');
        Route::match(['GET', 'QUERY'], 'templates', [TemplateController::class, 'index'])->name('templates');
        Route::match(['GET', 'QUERY'], 'templates/{template}', [TemplateController::class, 'show'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.show');
        Route::post('templates', [TemplateController::class, 'create'])->name('templates.create');
        Route::put('templates/{template}', [TemplateController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.update');
        Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.destroy');
        Route::put('templates/{template}/activation', [TemplateActivationController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.activation.update');
        Route::put('templates/{template}/position', [TemplatePositionController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.position.update');
        Route::post('templates/{template}/duplicate', [TemplateDuplicateController::class, 'create'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.duplicate.create');

        Route::match(['GET', 'QUERY'], 'templates/{template}/versions', [StructureVersionController::class, 'index'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.versions');
        Route::match(['GET', 'QUERY'], 'templates/{template}/versions/{version}', [StructureVersionController::class, 'show'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.show');

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
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/duplicates', [ContactDuplicateController::class, 'index'])->name('duplicates');
        Route::put('vaults/{vault}/duplicates/{candidate}/dismissal', [ContactDuplicateDismissalController::class, 'update'])->where(['candidate' => '[1-9][0-9]*'])->name('duplicates.dismissal.update');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contact-list-columns', [ContactListColumnController::class, 'show'])->name('contactListColumns.show');
        Route::put('vaults/{vault}/contact-list-columns', [ContactListColumnController::class, 'update'])->name('contactListColumns.update');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions', [PossessionController::class, 'index'])->name('possessions');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}', [PossessionController::class, 'show'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.show');
        Route::post('vaults/{vault}/possessions', [PossessionController::class, 'create'])->name('possessions.create');
        Route::put('vaults/{vault}/possessions/{possession}', [PossessionController::class, 'update'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.update');
        Route::delete('vaults/{vault}/possessions/{possession}', [PossessionController::class, 'destroy'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.destroy');
        Route::put('vaults/{vault}/possessions/{possession}/position', [PossessionPositionController::class, 'update'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.position');
        Route::put('vaults/{vault}/possessions/{possession}/values', [PossessionFieldValueController::class, 'update'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.values');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/timeline', [PossessionTimelineController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.timeline');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/transactions', [PossessionTransactionController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.transactions');
        Route::post('vaults/{vault}/possessions/{possession}/transactions', [PossessionTransactionController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.transactions.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/transactions/{transaction}', [PossessionTransactionController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'transaction' => '[0-9a-fA-F-]{36}'])->name('possessions.transactions.show');
        Route::put('vaults/{vault}/possessions/{possession}/transactions/{transaction}', [PossessionTransactionController::class, 'update'])->where(['possession' => '[0-9a-fA-F-]{36}', 'transaction' => '[0-9a-fA-F-]{36}'])->name('possessions.transactions.update');
        Route::delete('vaults/{vault}/possessions/{possession}/transactions/{transaction}', [PossessionTransactionController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'transaction' => '[0-9a-fA-F-]{36}'])->name('possessions.transactions.destroy');
        Route::post('vaults/{vault}/possessions/{possession}/transactions/{transaction}/documents', [PossessionTransactionDocumentController::class, 'create'])->middleware('flag:file-upload')->where(['possession' => '[0-9a-fA-F-]{36}', 'transaction' => '[0-9a-fA-F-]{36}'])->name('possessions.transactions.documents.create');
        Route::delete('vaults/{vault}/possessions/{possession}/transactions/{transaction}/documents/{file}', [PossessionTransactionDocumentController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'transaction' => '[0-9a-fA-F-]{36}', 'file' => '[0-9a-fA-F-]{36}'])->name('possessions.transactions.documents.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/insurance-records', [PossessionInsuranceRecordController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.insuranceRecords');
        Route::post('vaults/{vault}/possessions/{possession}/insurance-records', [PossessionInsuranceRecordController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.insuranceRecords.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}', [PossessionInsuranceRecordController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'insuranceRecord' => '[0-9a-fA-F-]{36}'])->name('possessions.insuranceRecords.show');
        Route::put('vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}', [PossessionInsuranceRecordController::class, 'update'])->where(['possession' => '[0-9a-fA-F-]{36}', 'insuranceRecord' => '[0-9a-fA-F-]{36}'])->name('possessions.insuranceRecords.update');
        Route::delete('vaults/{vault}/possessions/{possession}/insurance-records/{insuranceRecord}', [PossessionInsuranceRecordController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'insuranceRecord' => '[0-9a-fA-F-]{36}'])->name('possessions.insuranceRecords.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/valuations', [PossessionValuationController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.valuations');
        Route::post('vaults/{vault}/possessions/{possession}/valuations', [PossessionValuationController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.valuations.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/valuations/{valuation}', [PossessionValuationController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'valuation' => '[0-9a-fA-F-]{36}'])->name('possessions.valuations.show');
        Route::delete('vaults/{vault}/possessions/{possession}/valuations/{valuation}', [PossessionValuationController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'valuation' => '[0-9a-fA-F-]{36}'])->name('possessions.valuations.destroy');
        Route::post('vaults/{vault}/possessions/{possession}/valuations/{valuation}/documents', [PossessionValuationDocumentController::class, 'create'])->middleware('flag:file-upload')->where(['possession' => '[0-9a-fA-F-]{36}', 'valuation' => '[0-9a-fA-F-]{36}'])->name('possessions.valuations.documents.create');
        Route::delete('vaults/{vault}/possessions/{possession}/valuations/{valuation}/documents/{file}', [PossessionValuationDocumentController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'valuation' => '[0-9a-fA-F-]{36}', 'file' => '[0-9a-fA-F-]{36}'])->name('possessions.valuations.documents.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/provenance', [PossessionProvenanceEventController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.provenance');
        Route::post('vaults/{vault}/possessions/{possession}/provenance', [PossessionProvenanceEventController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.provenance.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/provenance/{event}', [PossessionProvenanceEventController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'event' => '[0-9a-fA-F-]{36}'])->name('possessions.provenance.show');
        Route::put('vaults/{vault}/possessions/{possession}/provenance/{event}', [PossessionProvenanceEventController::class, 'update'])->where(['possession' => '[0-9a-fA-F-]{36}', 'event' => '[0-9a-fA-F-]{36}'])->name('possessions.provenance.update');
        Route::delete('vaults/{vault}/possessions/{possession}/provenance/{event}', [PossessionProvenanceEventController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'event' => '[0-9a-fA-F-]{36}'])->name('possessions.provenance.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/location-stays', [PossessionLocationStayController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.locationStays');
        Route::post('vaults/{vault}/possessions/{possession}/location-stays', [PossessionLocationStayController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.locationStays.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/location-stays/{stay}', [PossessionLocationStayController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'stay' => '[0-9a-fA-F-]{36}'])->name('possessions.locationStays.show');
        Route::put('vaults/{vault}/possessions/{possession}/location-stays/{stay}', [PossessionLocationStayController::class, 'update'])->where(['possession' => '[0-9a-fA-F-]{36}', 'stay' => '[0-9a-fA-F-]{36}'])->name('possessions.locationStays.update');
        Route::delete('vaults/{vault}/possessions/{possession}/location-stays/{stay}', [PossessionLocationStayController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'stay' => '[0-9a-fA-F-]{36}'])->name('possessions.locationStays.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/loans', [PossessionLoanController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.loans');
        Route::post('vaults/{vault}/possessions/{possession}/loans', [PossessionLoanController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.loans.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/loans/{loan}', [PossessionLoanController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'loan' => '[0-9a-fA-F-]{36}'])->name('possessions.loans.show');
        Route::put('vaults/{vault}/possessions/{possession}/loans/{loan}', [PossessionLoanController::class, 'update'])->where(['possession' => '[0-9a-fA-F-]{36}', 'loan' => '[0-9a-fA-F-]{36}'])->name('possessions.loans.update');
        Route::delete('vaults/{vault}/possessions/{possession}/loans/{loan}', [PossessionLoanController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'loan' => '[0-9a-fA-F-]{36}'])->name('possessions.loans.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/maintenance-records', [PossessionMaintenanceRecordController::class, 'index'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.maintenanceRecords');
        Route::post('vaults/{vault}/possessions/{possession}/maintenance-records', [PossessionMaintenanceRecordController::class, 'create'])->where('possession', '[0-9a-fA-F-]{36}')->name('possessions.maintenanceRecords.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}', [PossessionMaintenanceRecordController::class, 'show'])->where(['possession' => '[0-9a-fA-F-]{36}', 'maintenanceRecord' => '[0-9a-fA-F-]{36}'])->name('possessions.maintenanceRecords.show');
        Route::put('vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}', [PossessionMaintenanceRecordController::class, 'update'])->where(['possession' => '[0-9a-fA-F-]{36}', 'maintenanceRecord' => '[0-9a-fA-F-]{36}'])->name('possessions.maintenanceRecords.update');
        Route::delete('vaults/{vault}/possessions/{possession}/maintenance-records/{maintenanceRecord}', [PossessionMaintenanceRecordController::class, 'destroy'])->where(['possession' => '[0-9a-fA-F-]{36}', 'maintenanceRecord' => '[0-9a-fA-F-]{36}'])->name('possessions.maintenanceRecords.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/knowledge/folders', [KnowledgeFolderController::class, 'index'])->name('knowledge.folders');
        Route::post('vaults/{vault}/knowledge/folders', [KnowledgeFolderController::class, 'create'])->name('knowledge.folders.create');
        Route::put('vaults/{vault}/knowledge/folders/{folder}', [KnowledgeFolderController::class, 'update'])->where('folder', '[0-9a-fA-F-]{36}')->name('knowledge.folders.update');
        Route::delete('vaults/{vault}/knowledge/folders/{folder}', [KnowledgeFolderController::class, 'destroy'])->where('folder', '[0-9a-fA-F-]{36}')->name('knowledge.folders.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/knowledge/notes', [KnowledgeNoteController::class, 'index'])->name('knowledge.notes');
        Route::post('vaults/{vault}/knowledge/notes', [KnowledgeNoteController::class, 'create'])->name('knowledge.notes.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/knowledge/notes/{note}', [KnowledgeNoteController::class, 'show'])->where('note', '[0-9a-fA-F-]{36}')->name('knowledge.notes.show');
        Route::put('vaults/{vault}/knowledge/notes/{note}', [KnowledgeNoteController::class, 'update'])->where('note', '[0-9a-fA-F-]{36}')->name('knowledge.notes.update');
        Route::delete('vaults/{vault}/knowledge/notes/{note}', [KnowledgeNoteController::class, 'destroy'])->where('note', '[0-9a-fA-F-]{36}')->name('knowledge.notes.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/knowledge/lists', [KnowledgeListController::class, 'index'])->name('knowledge.lists');
        Route::post('vaults/{vault}/knowledge/lists', [KnowledgeListController::class, 'create'])->name('knowledge.lists.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/knowledge/lists/{list}', [KnowledgeListController::class, 'show'])->where('list', '[0-9a-fA-F-]{36}')->name('knowledge.lists.show');
        Route::put('vaults/{vault}/knowledge/lists/{list}', [KnowledgeListController::class, 'update'])->where('list', '[0-9a-fA-F-]{36}')->name('knowledge.lists.update');
        Route::delete('vaults/{vault}/knowledge/lists/{list}', [KnowledgeListController::class, 'destroy'])->where('list', '[0-9a-fA-F-]{36}')->name('knowledge.lists.destroy');
        Route::post('vaults/{vault}/knowledge/lists/{list}/columns', [KnowledgeListColumnController::class, 'create'])->where('list', '[0-9a-fA-F-]{36}')->name('knowledge.lists.columns.create');
        Route::put('vaults/{vault}/knowledge/lists/{list}/columns/{column}', [KnowledgeListColumnController::class, 'update'])->where(['list' => '[0-9a-fA-F-]{36}', 'column' => '[0-9a-fA-F-]{36}'])->name('knowledge.lists.columns.update');
        Route::delete('vaults/{vault}/knowledge/lists/{list}/columns/{column}', [KnowledgeListColumnController::class, 'destroy'])->where(['list' => '[0-9a-fA-F-]{36}', 'column' => '[0-9a-fA-F-]{36}'])->name('knowledge.lists.columns.destroy');
        Route::post('vaults/{vault}/knowledge/lists/{list}/items', [KnowledgeListItemController::class, 'create'])->where('list', '[0-9a-fA-F-]{36}')->name('knowledge.lists.items.create');
        Route::put('vaults/{vault}/knowledge/lists/{list}/items/{item}', [KnowledgeListItemController::class, 'update'])->where(['list' => '[0-9a-fA-F-]{36}', 'item' => '[0-9a-fA-F-]{36}'])->name('knowledge.lists.items.update');
        Route::delete('vaults/{vault}/knowledge/lists/{list}/items/{item}', [KnowledgeListItemController::class, 'destroy'])->where(['list' => '[0-9a-fA-F-]{36}', 'item' => '[0-9a-fA-F-]{36}'])->name('knowledge.lists.items.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/knowledge/search', [KnowledgeSearchController::class, 'index'])->name('knowledge.search');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts', [ContactController::class, 'index'])->name('contacts');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contact-categories', [ContactCategoryController::class, 'index'])->name('contactCategories');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contact-summaries', [ContactSummaryController::class, 'index'])->name('contactSummaries');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/reminder-occurrences', [VaultReminderOccurrenceController::class, 'index'])->name('reminderOccurrences');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/reminders', [VaultReminderController::class, 'index'])->name('reminders');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/search', [SearchController::class, 'index'])->name('search');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/me', [ContactMeController::class, 'show'])->name('contacts.me');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/vcard', [ContactVCardController::class, 'show'])->middleware('flag:vcard-export')->name('contacts.vcard.show');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/markdown', [ContactMarkdownController::class, 'show'])->name('contacts.markdown.show');
        Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->name('contacts.create');
        Route::delete('vaults/{vault}/contacts', [ContactDeletionController::class, 'destroy'])->name('contacts.deletion.destroy');
        Route::put('vaults/{vault}/contacts/categories', [ContactCategoryController::class, 'update'])->name('contacts.categories.update');
        Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'show'])->name('contacts.merge.show');
        Route::post('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'create'])->name('contacts.merge.create');

        Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])->name('contacts.name.update');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/name-representations', [ContactNameRepresentationController::class, 'index'])->name('contacts.nameRepresentations');
        Route::put('vaults/{vault}/contacts/{contact}/name-representations', [ContactNameRepresentationController::class, 'update'])->name('contacts.nameRepresentations.update');

        Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->name('contacts.favourite.update');
        Route::put('vaults/{vault}/contacts/{contact}/partial', [ContactPartialController::class, 'update'])->name('contacts.partial.update');
        Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])->middleware('flag:file-upload')->name('contacts.avatar.update');
        Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->name('contacts.avatar.destroy');
        Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactStructureVersionController::class, 'update'])->name('contacts.templateVersion.update');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/template-preview', [ContactTemplatePreviewController::class, 'show'])->name('contacts.templatePreview.show');
        Route::put('vaults/{vault}/contacts/{contact}/template', [ContactTemplateController::class, 'update'])->name('contacts.template.update');
        Route::put('vaults/{vault}/contacts/{contact}/sections/{section}', [ContactSectionController::class, 'update'])->name('contacts.sections.update');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/reminders', [ContactReminderController::class, 'index'])->name('contacts.reminders');
        Route::post('vaults/{vault}/contacts/{contact}/reminders', [ContactReminderController::class, 'create'])->name('contacts.reminders.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/reminders/{reminder}', [ContactReminderController::class, 'show'])
            ->where('reminder', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminders.show');
        Route::put('vaults/{vault}/contacts/{contact}/reminders/{reminder}', [ContactReminderController::class, 'update'])
            ->where('reminder', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminders.update');
        Route::delete('vaults/{vault}/contacts/{contact}/reminders/{reminder}', [ContactReminderController::class, 'destroy'])
            ->where('reminder', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminders.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/reminder-occurrences', [ContactReminderOccurrenceController::class, 'index'])->name('contacts.reminderOccurrences');
        Route::put('vaults/{vault}/contacts/{contact}/reminder-occurrences/{occurrence}/answer', [ReminderOccurrenceAnswerController::class, 'update'])
            ->where('occurrence', '[0-9a-fA-F-]{36}')
            ->name('contacts.reminderOccurrences.answer.update');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/entities', [ContactEntityController::class, 'index'])->name('contacts.entities');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/relationships', [ContactRelationshipController::class, 'index'])->name('contacts.relationships');
        Route::post('vaults/{vault}/contacts/{contact}/relationships', [ContactRelationshipController::class, 'create'])->name('contacts.relationships.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/relationships/{relationship}', [ContactRelationshipController::class, 'show'])
            ->where('relationship', '[0-9a-fA-F-]{36}')
            ->name('contacts.relationships.show');
        Route::put('vaults/{vault}/contacts/{contact}/relationships/{relationship}', [ContactRelationshipController::class, 'update'])
            ->where('relationship', '[0-9a-fA-F-]{36}')
            ->name('contacts.relationships.update');
        Route::delete('vaults/{vault}/contacts/{contact}/relationships/{relationship}', [ContactRelationshipController::class, 'destroy'])
            ->where('relationship', '[0-9a-fA-F-]{36}')
            ->name('contacts.relationships.destroy');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/life-events', [LifeEventController::class, 'index'])->name('lifeEvents');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/life-event-slices', [LifeEventSliceController::class, 'index'])->name('lifeEventSlices');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/life-events/{lifeEvent}', [LifeEventController::class, 'show'])->where('lifeEvent', '[0-9a-fA-F-]{36}')->name('lifeEvents.show');
        Route::post('vaults/{vault}/life-events', [LifeEventController::class, 'create'])->name('lifeEvents.create');
        Route::put('vaults/{vault}/life-events/{lifeEvent}', [LifeEventController::class, 'update'])->where('lifeEvent', '[0-9a-fA-F-]{36}')->name('lifeEvents.update');
        Route::delete('vaults/{vault}/life-events/{lifeEvent}', [LifeEventController::class, 'destroy'])->where('lifeEvent', '[0-9a-fA-F-]{36}')->name('lifeEvents.destroy');
        Route::put('vaults/{vault}/life-events/{lifeEvent}/restoration', [LifeEventRestorationController::class, 'update'])->where('lifeEvent', '[0-9a-fA-F-]{36}')->name('lifeEvents.restoration.update');

        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups', [GroupController::class, 'index'])->middleware('flag:groups')->name('groups');
        Route::post('vaults/{vault}/groups', [GroupController::class, 'create'])->middleware('flag:groups')->name('groups.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}', [GroupController::class, 'show'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.show');
        Route::put('vaults/{vault}/groups/{group}', [GroupController::class, 'update'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.update');
        Route::delete('vaults/{vault}/groups/{group}', [GroupController::class, 'destroy'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.destroy');
        Route::put('vaults/{vault}/groups/{group}/restoration', [GroupRestorationController::class, 'update'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.restoration.update');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/members', [GroupMemberController::class, 'index'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.members');
        Route::post('vaults/{vault}/groups/{group}/members', [GroupMemberController::class, 'create'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.members.create');
        Route::put('vaults/{vault}/groups/{group}/members/{membership}', [GroupMemberController::class, 'update'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'membership' => '[0-9a-fA-F-]{36}'])->name('groups.members.update');
        Route::delete('vaults/{vault}/groups/{group}/members/{membership}', [GroupMemberController::class, 'destroy'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'membership' => '[0-9a-fA-F-]{36}'])->name('groups.members.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/files', [GroupFileController::class, 'index'])->middleware(['flag:groups', 'flag:file-upload'])->where('group', '[0-9a-fA-F-]{36}')->name('groups.files');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/files/{file}', [GroupFileController::class, 'show'])->middleware(['flag:groups', 'flag:file-upload'])->where(['group' => '[0-9a-fA-F-]{36}', 'file' => '[0-9a-fA-F-]{36}'])->name('groups.files.show');
        Route::post('vaults/{vault}/groups/{group}/files', [GroupFileController::class, 'create'])->middleware(['flag:groups', 'flag:file-upload'])->where('group', '[0-9a-fA-F-]{36}')->name('groups.files.create');
        Route::delete('vaults/{vault}/groups/{group}/files/{file}', [GroupFileController::class, 'destroy'])->middleware(['flag:groups', 'flag:file-upload'])->where(['group' => '[0-9a-fA-F-]{36}', 'file' => '[0-9a-fA-F-]{36}'])->name('groups.files.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/files/{file}/content', [GroupFileContentController::class, 'show'])->middleware(['flag:groups', 'flag:file-upload'])->where(['group' => '[0-9a-fA-F-]{36}', 'file' => '[0-9a-fA-F-]{36}'])->name('groups.files.content.show');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/notes', [GroupNoteController::class, 'index'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.notes');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/notes/{note}', [GroupNoteController::class, 'show'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'note' => '[0-9a-fA-F-]{36}'])->name('groups.notes.show');
        Route::post('vaults/{vault}/groups/{group}/notes', [GroupNoteController::class, 'create'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.notes.create');
        Route::put('vaults/{vault}/groups/{group}/notes/{note}', [GroupNoteController::class, 'update'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'note' => '[0-9a-fA-F-]{36}'])->name('groups.notes.update');
        Route::delete('vaults/{vault}/groups/{group}/notes/{note}', [GroupNoteController::class, 'destroy'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'note' => '[0-9a-fA-F-]{36}'])->name('groups.notes.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/life-events', [GroupLifeEventController::class, 'index'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.lifeEvents');
        Route::post('vaults/{vault}/groups/{group}/life-events', [GroupLifeEventController::class, 'create'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.lifeEvents.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/reminders', [GroupReminderController::class, 'index'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.reminders');
        Route::post('vaults/{vault}/groups/{group}/reminders', [GroupReminderController::class, 'create'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.reminders.create');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/reminders/{reminder}', [GroupReminderController::class, 'show'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'reminder' => '[0-9a-fA-F-]{36}'])->name('groups.reminders.show');
        Route::put('vaults/{vault}/groups/{group}/reminders/{reminder}', [GroupReminderController::class, 'update'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'reminder' => '[0-9a-fA-F-]{36}'])->name('groups.reminders.update');
        Route::delete('vaults/{vault}/groups/{group}/reminders/{reminder}', [GroupReminderController::class, 'destroy'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'reminder' => '[0-9a-fA-F-]{36}'])->name('groups.reminders.destroy');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/groups/{group}/reminder-occurrences', [GroupReminderOccurrenceController::class, 'index'])->middleware('flag:groups')->where('group', '[0-9a-fA-F-]{36}')->name('groups.reminderOccurrences');
        Route::put('vaults/{vault}/groups/{group}/reminder-occurrences/{occurrence}/answer', [GroupReminderOccurrenceAnswerController::class, 'update'])->middleware('flag:groups')->where(['group' => '[0-9a-fA-F-]{36}', 'occurrence' => '[0-9a-fA-F-]{36}'])->name('groups.reminderOccurrences.answer.update');
        Route::match(['GET', 'QUERY'], 'vaults/{vault}/contacts/{contact}/groups', [ContactGroupController::class, 'index'])->middleware('flag:groups')->name('contacts.groups');

        Route::match(['GET', 'QUERY'], 'exports', [AccountExportController::class, 'index'])->name('accountExports');
        Route::match(['GET', 'QUERY'], 'exports/{accountExport}', [AccountExportController::class, 'show'])->where('accountExport', '[0-9a-fA-F-]{36}')->name('accountExports.show');
        Route::post('exports', [AccountExportController::class, 'create'])->middleware('throttle:20,1')->name('accountExports.create');
        Route::match(['GET', 'QUERY'], 'exports/{accountExport}/download', [AccountExportDownloadController::class, 'show'])->where('accountExport', '[0-9a-fA-F-]{36}')->name('accountExports.download.show');

        Route::match(['GET', 'QUERY'], 'imports/{accountImport}', [AccountImportController::class, 'show'])->where('accountImport', '[0-9a-fA-F-]{36}')->name('accountImports.show');
        Route::post('imports', [AccountImportController::class, 'create'])->middleware('throttle:20,1')->name('accountImports.create');
        Route::put('imports/{accountImport}', [AccountImportController::class, 'update'])->where('accountImport', '[0-9a-fA-F-]{36}')->name('accountImports.update');
        Route::delete('imports/{accountImport}', [AccountImportController::class, 'destroy'])->where('accountImport', '[0-9a-fA-F-]{36}')->name('accountImports.destroy');
        Route::match(['GET', 'QUERY'], 'imports/{accountImport}/contacts', [AccountImportContactController::class, 'index'])->where('accountImport', '[0-9a-fA-F-]{36}')->name('accountImports.contacts');
        Route::match(['GET', 'QUERY'], 'imports/{accountImport}/report', [AccountImportReportController::class, 'index'])->where('accountImport', '[0-9a-fA-F-]{36}')->name('accountImports.report');
    });
});
