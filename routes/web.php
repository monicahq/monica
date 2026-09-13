<?php

declare(strict_types=1);

use App\Enums\ContactGroupingEnum;
use App\Enums\SsoProviderEnum;
use App\Http\Controllers\App\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\App\Admin\AccountLifetimeAccessController;
use App\Http\Controllers\App\Admin\AccountStorageLimitController;
use App\Http\Controllers\App\Admin\AdministratorController;
use App\Http\Controllers\App\Admin\AdministratorLogController;
use App\Http\Controllers\App\Admin\ModerationApprovalController;
use App\Http\Controllers\App\Admin\ModerationController;
use App\Http\Controllers\App\Admin\ModerationRejectionController;
use App\Http\Controllers\App\Admin\OverviewController;
use App\Http\Controllers\App\Admin\UserActivationController;
use App\Http\Controllers\App\Docs\ApiDocsController;
use App\Http\Controllers\App\Docs\ApiDocsMarkdownController;
use App\Http\Controllers\App\GettingStartedController;
use App\Http\Controllers\App\HomeController;
use App\Http\Controllers\App\Marketplace\MarketplaceController;
use App\Http\Controllers\App\NotificationController;
use App\Http\Controllers\App\NotificationReadController;
use App\Http\Controllers\App\Settings\Account\AccountController;
use App\Http\Controllers\App\Settings\Account\Billing\BillingController;
use App\Http\Controllers\App\Settings\Account\Billing\BillingCurrencyController;
use App\Http\Controllers\App\Settings\Account\Billing\CheckoutController;
use App\Http\Controllers\App\Settings\Account\Billing\CheckoutReturnController;
use App\Http\Controllers\App\Settings\Account\Billing\InvoiceController;
use App\Http\Controllers\App\Settings\Account\Billing\SubscriptionCancellationController;
use App\Http\Controllers\App\Settings\Account\Billing\SubscriptionController;
use App\Http\Controllers\App\Settings\Account\Billing\SubscriptionPortalController;
use App\Http\Controllers\App\Settings\Account\EntityTypes\EntityTypeController;
use App\Http\Controllers\App\Settings\Account\EntityTypes\EntityTypePositionController;
use App\Http\Controllers\App\Settings\Account\Logs\EmailSentController;
use App\Http\Controllers\App\Settings\Account\Logs\LogController;
use App\Http\Controllers\App\Settings\Account\Notifications\ChannelConnectionController;
use App\Http\Controllers\App\Settings\Account\Notifications\ChannelProofController;
use App\Http\Controllers\App\Settings\Account\Notifications\ChannelTestController;
use App\Http\Controllers\App\Settings\Account\Notifications\NotificationChannelController;
use App\Http\Controllers\App\Settings\Account\Notifications\TelegramWebhookController;
use App\Http\Controllers\App\Settings\Account\Preferences\PreferenceController;
use App\Http\Controllers\App\Settings\Account\Preferences\TimezoneController;
use App\Http\Controllers\App\Settings\Account\Profile\EmailController;
use App\Http\Controllers\App\Settings\Account\Profile\PhotoController;
use App\Http\Controllers\App\Settings\Account\Profile\ProfileController;
use App\Http\Controllers\App\Settings\Account\RelationshipTypes\RelationshipCategoryController;
use App\Http\Controllers\App\Settings\Account\RelationshipTypes\RelationshipCategoryPositionController;
use App\Http\Controllers\App\Settings\Account\RelationshipTypes\RelationshipTypeController;
use App\Http\Controllers\App\Settings\Account\RelationshipTypes\RelationshipTypePositionController;
use App\Http\Controllers\App\Settings\Account\Security\ApiKeyController;
use App\Http\Controllers\App\Settings\Account\Security\PasskeyController;
use App\Http\Controllers\App\Settings\Account\Security\PasswordController;
use App\Http\Controllers\App\Settings\Account\Security\RecoveryCodeController;
use App\Http\Controllers\App\Settings\Account\Security\SecurityController;
use App\Http\Controllers\App\Settings\Account\Security\SsoController;
use App\Http\Controllers\App\Settings\Account\Security\TwoFactorController;
use App\Http\Controllers\App\Settings\Account\Templates\FieldController;
use App\Http\Controllers\App\Settings\Account\Templates\FieldPositionController;
use App\Http\Controllers\App\Settings\Account\Templates\SectionExportController;
use App\Http\Controllers\App\Settings\Account\Templates\SectionImportCheckController;
use App\Http\Controllers\App\Settings\Account\Templates\SectionImportController;
use App\Http\Controllers\App\Settings\Account\Templates\SectionShareController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateActivationController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateBuilderController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateCopyController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateExportController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateImportCheckController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateImportController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplatePageController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplatePagePositionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateSectionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateSectionPageController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateSectionPositionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateShareController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateVersionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateVersionHistoryController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateVersionPublicationController;
use App\Http\Controllers\App\Settings\Account\ValueLists\ValueListController;
use App\Http\Controllers\App\Settings\Account\ValueLists\ValueListItemActivationController;
use App\Http\Controllers\App\Settings\Account\ValueLists\ValueListItemController;
use App\Http\Controllers\App\Settings\Account\ValueLists\ValueListItemPositionController;
use App\Http\Controllers\App\Settings\SettingsController;
use App\Http\Controllers\App\SharedContactController;
use App\Http\Controllers\App\SharedContactPasswordController;
use App\Http\Controllers\App\Vaults\AbsorbedContactController;
use App\Http\Controllers\App\Vaults\ContactAvatarController;
use App\Http\Controllers\App\Vaults\ContactController;
use App\Http\Controllers\App\Vaults\ContactDuplicateController;
use App\Http\Controllers\App\Vaults\ContactDuplicateDismissalController;
use App\Http\Controllers\App\Vaults\ContactDuplicateSearchController;
use App\Http\Controllers\App\Vaults\ContactFavouriteController;
use App\Http\Controllers\App\Vaults\ContactMergeController;
use App\Http\Controllers\App\Vaults\ContactNameController;
use App\Http\Controllers\App\Vaults\ContactRelationshipController;
use App\Http\Controllers\App\Vaults\ContactSectionController;
use App\Http\Controllers\App\Vaults\ContactShareLinkController;
use App\Http\Controllers\App\Vaults\ContactTemplateVersionController;
use App\Http\Controllers\App\Vaults\FileController;
use App\Http\Controllers\App\Vaults\ReminderController;
use App\Http\Controllers\App\Vaults\ReminderOccurrenceDismissalController;
use App\Http\Controllers\App\Vaults\ReminderOccurrencePostponementController;
use App\Http\Controllers\App\Vaults\ReminderOccurrenceTestNotificationController;
use App\Http\Controllers\App\Vaults\ReminderPauseController;
use App\Http\Controllers\App\Vaults\SetAsideValueController;
use App\Http\Controllers\App\Vaults\VaultController;
use App\Http\Controllers\App\Vaults\VaultDefaultTemplateController;
use App\Http\Controllers\App\Vaults\VaultReminderController;
use App\Http\Controllers\App\Vaults\VaultSettingsController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home.index');

Route::get('docs/api', [ApiDocsController::class, 'index'])
    ->name('docs.api.index');

Route::get('docs/api.md', [ApiDocsMarkdownController::class, 'index'])
    ->name('docs.api.markdown.index');

Route::get('docs/api/{section}.md', [ApiDocsMarkdownController::class, 'show'])
    ->where('section', '[a-z0-9\-]+')
    ->name('docs.api.markdown.show');

Route::get('docs/api/{section}', [ApiDocsController::class, 'show'])
    ->where('section', '[a-z0-9\-]+')
    ->name('docs.api.show');

Route::get('marketplace', [MarketplaceController::class, 'index'])
    ->name('marketplace.index');

Route::get('s/{token}/{page?}', [SharedContactController::class, 'show'])
    ->middleware(['throttle:60,1'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->name('share.show');

Route::post('s/{token}', [SharedContactPasswordController::class, 'create'])
    ->middleware(['throttle:20,1'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->name('share.password.create');

Route::middleware(['auth', 'active', 'verified:auth.verification.notice'])->group(function (): void {
    Route::get('getting-started', [GettingStartedController::class, 'index'])->name('gettingStarted.index');

    Route::get('vaults', [VaultController::class, 'index'])->name('vaults.index');
    Route::get('vaults/new', [VaultController::class, 'new'])->name('vaults.new');
    Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
    Route::get('vaults/{vault}', [VaultController::class, 'show'])->name('vaults.show');
    Route::put('vaults/{vault}', [VaultController::class, 'update'])->name('vaults.update');
    Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->name('vaults.destroy');

    Route::get('vaults/{vault}/files/{file}', [FileController::class, 'show'])
        ->whereUuid('file')
        ->name('files.show');

    Route::get('vaults/{vault}/contacts/new', [ContactController::class, 'new'])->name('contacts.new');
    Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->name('contacts.create');

    Route::get('vaults/{vault}/contacts/{filter?}', [ContactController::class, 'index'])
        ->whereIn('filter', [ContactGroupingEnum::Favourites->value, ContactGroupingEnum::Archived->value])
        ->name('contacts.index');

    Route::get('vaults/{vault}/contacts/{contact}/set-aside', [SetAsideValueController::class, 'index'])
        ->name('contacts.setAside.index');

    Route::get('vaults/{vault}/contacts/{contact}/edit', [ContactController::class, 'edit'])
        ->name('contacts.edit');

    Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])
        ->name('contacts.name.update');

    Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])
        ->name('contacts.avatar.update');

    Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])
        ->name('contacts.avatar.destroy');

    Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])
        ->name('contacts.favourite.update');

    Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])
        ->name('contacts.destroy');

    Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactTemplateVersionController::class, 'update'])
        ->name('contacts.templateVersion.update');

    Route::get('vaults/{vault}/contacts/{contact}/sections/{templateVersionSection}/edit', [ContactSectionController::class, 'edit'])
        ->whereNumber('templateVersionSection')
        ->name('contacts.sections.edit');

    Route::put('vaults/{vault}/contacts/{contact}/sections/{templateVersionSection}', [ContactSectionController::class, 'update'])
        ->whereNumber('templateVersionSection')
        ->name('contacts.sections.update');

    Route::get('vaults/{vault}/contacts/{contact}/share-links', [ContactShareLinkController::class, 'index'])
        ->name('contacts.shareLinks.index');

    Route::get('vaults/{vault}/contacts/{contact}/share-links/new', [ContactShareLinkController::class, 'new'])
        ->name('contacts.shareLinks.new');

    Route::post('vaults/{vault}/contacts/{contact}/share-links', [ContactShareLinkController::class, 'create'])
        ->name('contacts.shareLinks.create');

    Route::get('vaults/{vault}/contacts/{contact}/share-links/{shareLink}', [ContactShareLinkController::class, 'show'])
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.show');

    Route::get('vaults/{vault}/contacts/{contact}/share-links/{shareLink}/edit', [ContactShareLinkController::class, 'edit'])
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.edit');

    Route::put('vaults/{vault}/contacts/{contact}/share-links/{shareLink}', [ContactShareLinkController::class, 'update'])
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.update');

    Route::delete('vaults/{vault}/contacts/{contact}/share-links/{shareLink}', [ContactShareLinkController::class, 'destroy'])
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.destroy');

    Route::get('vaults/{vault}/contacts/{contact}/merge', [ContactMergeController::class, 'new'])
        ->name('contacts.merge.new');

    Route::get('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'show'])
        ->name('contacts.merge.show');

    Route::post('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'create'])
        ->name('contacts.merge.create');

    Route::get('vaults/{vault}/contacts/{contact}/relationships/new', [ContactRelationshipController::class, 'new'])
        ->name('contacts.relationships.new');

    Route::post('vaults/{vault}/contacts/{contact}/relationships', [ContactRelationshipController::class, 'create'])
        ->name('contacts.relationships.create');

    Route::get('vaults/{vault}/contacts/{contact}/{page?}', [ContactController::class, 'show'])
        ->missing(fn (Request $request): Response => new AbsorbedContactController()->show($request))
        ->name('contacts.show');

    Route::delete('vaults/{vault}/merges/{contactMerge}', [ContactMergeController::class, 'destroy'])
        ->whereUuid('contactMerge')
        ->name('vaults.merges.destroy');

    Route::get('vaults/{vault}/duplicates', [ContactDuplicateController::class, 'index'])->name('vaults.duplicates.index');
    Route::post('vaults/{vault}/duplicates/search', [ContactDuplicateSearchController::class, 'create'])
        ->middleware('throttle:10,1')
        ->name('vaults.duplicates.search.create');
    Route::put('vaults/{vault}/duplicates/{candidate}/dismissal', [ContactDuplicateDismissalController::class, 'update'])
        ->whereNumber('candidate')
        ->name('vaults.duplicates.dismissal.update');

    Route::get('vaults/{vault}/reminders', [VaultReminderController::class, 'index'])->name('reminders.index');
    Route::get('vaults/{vault}/reminders/by-contact', [VaultReminderController::class, 'byContact'])->name('reminders.byContact');
    Route::get('vaults/{vault}/reminders/sent', [VaultReminderController::class, 'sent'])->name('reminders.sent');
    Route::put('vaults/{vault}/reminders/occurrences/{reminderOccurrence}/postponement', [ReminderOccurrencePostponementController::class, 'update'])
        ->whereUuid('reminderOccurrence')
        ->name('reminders.occurrences.postponement.update');
    Route::put('vaults/{vault}/reminders/occurrences/{reminderOccurrence}/dismissal', [ReminderOccurrenceDismissalController::class, 'update'])
        ->whereUuid('reminderOccurrence')
        ->name('reminders.occurrences.dismissal.update');
    Route::post('vaults/{vault}/reminders/occurrences/{reminderOccurrence}/test-notification', [ReminderOccurrenceTestNotificationController::class, 'create'])
        ->whereUuid('reminderOccurrence')
        ->middleware('throttle:20,1')
        ->name('reminders.occurrences.testNotification.create');
    Route::get('vaults/{vault}/reminders/{reminder}', [ReminderController::class, 'show'])
        ->whereUuid('reminder')
        ->name('reminders.show');
    Route::delete('vaults/{vault}/reminders/{reminder}', [ReminderController::class, 'destroy'])
        ->whereUuid('reminder')
        ->name('reminders.destroy');
    Route::put('vaults/{vault}/reminders/{reminder}/pause', [ReminderPauseController::class, 'update'])
        ->whereUuid('reminder')
        ->name('reminders.pause.update');
    Route::delete('vaults/{vault}/reminders/{reminder}/pause', [ReminderPauseController::class, 'destroy'])
        ->whereUuid('reminder')
        ->name('reminders.pause.destroy');

    Route::get('vaults/{vault}/settings', [VaultSettingsController::class, 'index'])->name('vaults.settings.index');
    Route::put('vaults/{vault}/settings/default-template', [VaultDefaultTemplateController::class, 'update'])->name('vaults.defaultTemplate.update');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::put('notifications/read', [NotificationReadController::class, 'updateAll'])->name('notifications.readAll.update');
    Route::put('notifications/{notification}/read', [NotificationReadController::class, 'update'])->whereUuid('notification')->name('notifications.read.update');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::get('settings/account/profile', [ProfileController::class, 'index'])->name('settings.profile.index');
    Route::put('settings/account/profile', [ProfileController::class, 'update'])->name('settings.profile.update');

    /*
     * Changing the address every way back into the account points at, and destroying the account,
     * both cost a credential. The names beside them do not, which is why the address has a route of
     * its own rather than riding along with the profile.
     */
    Route::middleware(['password.confirm:auth.confirmPassword.new'])->group(function (): void {
        Route::put('settings/account/profile/email', [EmailController::class, 'update'])->name('settings.email.update');
        Route::delete('settings/account', [AccountController::class, 'destroy'])->name('settings.account.destroy');
    });

    Route::get('settings/account/billing', [BillingController::class, 'index'])->name('settings.billing.index');
    Route::put('settings/account/billing/currency', [BillingCurrencyController::class, 'update'])->name('settings.billingCurrency.update');
    Route::post('settings/account/billing/checkout/{price}', [CheckoutController::class, 'store'])->where('price', '[A-Za-z0-9_]+')->name('settings.checkout.store');
    Route::get('settings/account/billing/checkout/success', [CheckoutReturnController::class, 'show'])->name('settings.checkoutReturn.show');
    Route::put('settings/account/billing/subscription/{price}', [SubscriptionController::class, 'update'])->where('price', '[A-Za-z0-9_]+')->name('settings.subscription.update');
    Route::delete('settings/account/billing/subscription', [SubscriptionController::class, 'destroy'])->name('settings.subscription.destroy');
    Route::delete('settings/account/billing/subscription/cancellation', [SubscriptionCancellationController::class, 'destroy'])->name('settings.subscriptionCancellation.destroy');
    Route::get('settings/account/billing/subscription/portal', [SubscriptionPortalController::class, 'show'])->name('settings.subscriptionPortal.show');
    Route::get('settings/account/billing/invoices', [InvoiceController::class, 'index'])->name('settings.invoices.index');
    Route::get('settings/account/billing/invoices/{payment}', [InvoiceController::class, 'show'])->where('payment', '[A-Za-z0-9_]+')->name('settings.invoices.show');
    Route::get('settings/account/logs', [LogController::class, 'index'])->name('settings.logs.index');
    Route::get('settings/account/logs/emails', [EmailSentController::class, 'index'])->name('settings.emailsSent.index');

    Route::get('settings/account/preferences', [PreferenceController::class, 'index'])->name('settings.preferences.index');
    Route::put('settings/account/preferences', [PreferenceController::class, 'update'])->name('settings.preferences.update');
    Route::put('settings/account/preferences/timezone', [TimezoneController::class, 'update'])->name('settings.timezone.update');

    Route::get('settings/account/notifications', [NotificationChannelController::class, 'index'])->name('settings.notifications.index');
    Route::put('settings/account/notifications/{channel}', [NotificationChannelController::class, 'update'])->name('settings.notifications.update');
    Route::post('settings/account/notifications/{channel}/proof', [ChannelProofController::class, 'create'])->name('settings.notifications.proof.store');
    Route::put('settings/account/notifications/{channel}/proof', [ChannelProofController::class, 'update'])->name('settings.notifications.proof.update');
    Route::post('settings/account/notifications/{channel}/test', [ChannelTestController::class, 'create'])->name('settings.notifications.test.store');
    Route::post('settings/account/notifications/{channel}/connection', [ChannelConnectionController::class, 'create'])->name('settings.notifications.connection.store');
    Route::post('settings/account/notifications/{channel}/reset', [NotificationChannelController::class, 'reset'])->name('settings.notifications.reset');

    Route::get('settings/account/value-lists', [ValueListController::class, 'index'])->name('settings.valueLists.index');
    Route::post('settings/account/value-lists', [ValueListController::class, 'create'])->name('settings.valueLists.create');
    Route::put('settings/account/value-lists/{valueList}', [ValueListController::class, 'update'])->whereNumber('valueList')->name('settings.valueLists.update');
    Route::delete('settings/account/value-lists/{valueList}', [ValueListController::class, 'destroy'])->whereNumber('valueList')->name('settings.valueLists.destroy');

    Route::post('settings/account/value-lists/{valueList}/values', [ValueListItemController::class, 'create'])->whereNumber('valueList')->name('settings.valueListItems.create');
    Route::put('settings/account/value-lists/values/{valueListItem}', [ValueListItemController::class, 'update'])->whereNumber('valueListItem')->name('settings.valueListItems.update');
    Route::delete('settings/account/value-lists/values/{valueListItem}', [ValueListItemController::class, 'destroy'])->whereNumber('valueListItem')->name('settings.valueListItems.destroy');
    Route::put('settings/account/value-lists/values/{valueListItem}/position', [ValueListItemPositionController::class, 'update'])->whereNumber('valueListItem')->name('settings.valueListItemPosition.update');
    Route::put('settings/account/value-lists/values/{valueListItem}/active', [ValueListItemActivationController::class, 'update'])->whereNumber('valueListItem')->name('settings.valueListItemActivation.update');

    Route::get('settings/account/entity-types', [EntityTypeController::class, 'index'])->name('settings.entityTypes.index');
    Route::get('settings/account/entity-types/new', [EntityTypeController::class, 'new'])->name('settings.entityTypes.new');
    Route::post('settings/account/entity-types', [EntityTypeController::class, 'create'])->name('settings.entityTypes.create');
    Route::get('settings/account/entity-types/{entityType}/edit', [EntityTypeController::class, 'edit'])->name('settings.entityTypes.edit');
    Route::put('settings/account/entity-types/{entityType}', [EntityTypeController::class, 'update'])->name('settings.entityTypes.update');
    Route::delete('settings/account/entity-types/{entityType}', [EntityTypeController::class, 'destroy'])->name('settings.entityTypes.destroy');
    Route::put('settings/account/entity-types/{entityType}/position', [EntityTypePositionController::class, 'update'])->name('settings.entityTypePosition.update');

    Route::get('settings/account/relationship-types', [RelationshipTypeController::class, 'index'])->name('settings.relationshipTypes.index');
    Route::get('settings/account/relationship-types/new', [RelationshipTypeController::class, 'new'])->name('settings.relationshipTypes.new');
    Route::post('settings/account/relationship-types', [RelationshipTypeController::class, 'create'])->name('settings.relationshipTypes.create');
    Route::get('settings/account/relationship-types/{relationshipType}/edit', [RelationshipTypeController::class, 'edit'])->whereNumber('relationshipType')->name('settings.relationshipTypes.edit');
    Route::put('settings/account/relationship-types/{relationshipType}', [RelationshipTypeController::class, 'update'])->whereNumber('relationshipType')->name('settings.relationshipTypes.update');
    Route::delete('settings/account/relationship-types/{relationshipType}', [RelationshipTypeController::class, 'destroy'])->whereNumber('relationshipType')->name('settings.relationshipTypes.destroy');
    Route::put('settings/account/relationship-types/{relationshipType}/position', [RelationshipTypePositionController::class, 'update'])->whereNumber('relationshipType')->name('settings.relationshipTypePosition.update');

    Route::post('settings/account/relationship-types/categories', [RelationshipCategoryController::class, 'create'])->name('settings.relationshipCategories.create');
    Route::put('settings/account/relationship-types/categories/{relationshipCategory}', [RelationshipCategoryController::class, 'update'])->whereNumber('relationshipCategory')->name('settings.relationshipCategories.update');
    Route::delete('settings/account/relationship-types/categories/{relationshipCategory}', [RelationshipCategoryController::class, 'destroy'])->whereNumber('relationshipCategory')->name('settings.relationshipCategories.destroy');
    Route::put('settings/account/relationship-types/categories/{relationshipCategory}/position', [RelationshipCategoryPositionController::class, 'update'])->whereNumber('relationshipCategory')->name('settings.relationshipCategoryPosition.update');

    Route::get('settings/account/templates', [TemplateController::class, 'index'])->name('settings.templates.index');
    Route::get('settings/account/templates/new', [TemplateController::class, 'new'])->name('settings.templates.new');
    Route::post('settings/account/templates', [TemplateController::class, 'create'])->name('settings.templates.create');
    Route::get('settings/account/templates/{template}/edit', [TemplateController::class, 'edit'])->name('settings.templates.edit');
    Route::put('settings/account/templates/{template}', [TemplateController::class, 'update'])->name('settings.templates.update');
    Route::delete('settings/account/templates/{template}', [TemplateController::class, 'destroy'])->name('settings.templates.destroy');
    Route::post('settings/account/templates/{template}/copy', [TemplateCopyController::class, 'create'])->name('settings.templateCopy.create');
    Route::put('settings/account/templates/{template}/active', [TemplateActivationController::class, 'update'])->name('settings.templateActivation.update');

    Route::get('settings/account/templates/import', [TemplateImportController::class, 'new'])->name('settings.templateImport.new');
    Route::post('settings/account/templates/import/check', [TemplateImportCheckController::class, 'create'])->middleware('throttle:20,1')->name('settings.templateImportCheck.create');
    Route::post('settings/account/templates/import', [TemplateImportController::class, 'create'])->middleware('throttle:20,1')->name('settings.templateImport.create');

    Route::get('settings/account/templates/{template}/pages/{templateVersionPage}/sections/import', [SectionImportController::class, 'new'])->name('settings.sectionImport.new');
    Route::post('settings/account/templates/{template}/pages/{templateVersionPage}/sections/import/check', [SectionImportCheckController::class, 'create'])->middleware('throttle:20,1')->name('settings.sectionImportCheck.create');
    Route::post('settings/account/templates/{template}/pages/{templateVersionPage}/sections/import', [SectionImportController::class, 'create'])->middleware('throttle:20,1')->name('settings.sectionImport.create');

    Route::get('settings/account/templates/{template}/share', [TemplateShareController::class, 'index'])->name('settings.templateShare.index');
    Route::get('settings/account/templates/{template}/export', [TemplateExportController::class, 'index'])->name('settings.templateExport.index');
    Route::get('settings/account/templates/{template}/export/file', [TemplateExportController::class, 'show'])->name('settings.templateExport.show');

    Route::get('settings/account/templates/{template}/sections/{templateVersionSection}/share', [SectionShareController::class, 'index'])->whereNumber('templateVersionSection')->name('settings.sectionShare.index');
    Route::get('settings/account/templates/{template}/sections/{templateVersionSection}/export', [SectionExportController::class, 'index'])->whereNumber('templateVersionSection')->name('settings.sectionExport.index');
    Route::get('settings/account/templates/{template}/sections/{templateVersionSection}/export/file', [SectionExportController::class, 'show'])->whereNumber('templateVersionSection')->name('settings.sectionExport.show');

    Route::get('settings/account/templates/{template}/builder/{page?}', [TemplateBuilderController::class, 'index'])->name('settings.templateBuilder.index');
    Route::get('settings/account/templates/{template}/builder/{page}/fields/{field}', [TemplateBuilderController::class, 'index'])->whereNumber('field')->name('settings.templateBuilder.field');
    Route::get('settings/account/templates/{template}/versions', [TemplateVersionHistoryController::class, 'index'])->name('settings.templateVersions.index');
    Route::post('settings/account/templates/{template}/versions', [TemplateVersionController::class, 'create'])->name('settings.templateVersions.create');
    Route::delete('settings/account/templates/{template}/versions/{templateVersion}', [TemplateVersionController::class, 'destroy'])->whereNumber('templateVersion')->name('settings.templateVersions.destroy');
    Route::put('settings/account/templates/{template}/versions/{templateVersion}/published', [TemplateVersionPublicationController::class, 'update'])->whereNumber('templateVersion')->name('settings.templateVersionPublication.update');

    Route::post('settings/account/templates/{template}/pages', [TemplatePageController::class, 'create'])->name('settings.templatePages.create');
    Route::put('settings/account/templates/{template}/pages/{templateVersionPage}', [TemplatePageController::class, 'update'])->name('settings.templatePages.update');
    Route::delete('settings/account/templates/{template}/pages/{templateVersionPage}', [TemplatePageController::class, 'destroy'])->name('settings.templatePages.destroy');
    Route::put('settings/account/templates/{template}/pages/{templateVersionPage}/position', [TemplatePagePositionController::class, 'update'])->name('settings.templatePagePosition.update');

    Route::post('settings/account/templates/{template}/sections', [TemplateSectionController::class, 'create'])->name('settings.templateSections.create');
    Route::put('settings/account/templates/{template}/sections/{templateVersionSection}', [TemplateSectionController::class, 'update'])->whereNumber('templateVersionSection')->name('settings.templateSections.update');
    Route::delete('settings/account/templates/{template}/sections/{templateVersionSection}', [TemplateSectionController::class, 'destroy'])->whereNumber('templateVersionSection')->name('settings.templateSections.destroy');
    Route::put('settings/account/templates/{template}/sections/{templateVersionSection}/position', [TemplateSectionPositionController::class, 'update'])->whereNumber('templateVersionSection')->name('settings.templateSectionPosition.update');
    Route::put('settings/account/templates/{template}/sections/{templateVersionSection}/page', [TemplateSectionPageController::class, 'update'])->whereNumber('templateVersionSection')->name('settings.templateSectionPage.update');

    Route::get('settings/account/templates/{template}/sections/{templateVersionSection}/fields/new', [FieldController::class, 'new'])->whereNumber('templateVersionSection')->name('settings.fields.new');
    Route::post('settings/account/templates/{template}/sections/{templateVersionSection}/fields', [FieldController::class, 'create'])->whereNumber('templateVersionSection')->name('settings.fields.create');
    Route::put('settings/account/templates/{template}/sections/{templateVersionSection}/fields/{field}', [FieldController::class, 'update'])->whereNumber(['templateVersionSection', 'field'])->name('settings.fields.update');
    Route::delete('settings/account/templates/{template}/sections/{templateVersionSection}/fields/{field}', [FieldController::class, 'destroy'])->whereNumber(['templateVersionSection', 'field'])->name('settings.fields.destroy');
    Route::put('settings/account/templates/{template}/sections/{templateVersionSection}/fields/{field}/position', [FieldPositionController::class, 'update'])->whereNumber(['templateVersionSection', 'field'])->name('settings.fieldPosition.update');

    Route::get('settings/account/security', [SecurityController::class, 'index'])->name('settings.security.index');

    /*
     * Everything that decides how somebody signs in, or hands out access outliving the session.
     * Reading the screen above costs nothing; changing any of it costs a password or a passkey,
     * which `password.confirm` reads from the session and the screen it names writes there.
     */
    Route::middleware(['password.confirm:auth.confirmPassword.new'])->group(function (): void {
        Route::post('settings/account/security/password', [PasswordController::class, 'create'])->name('settings.password.create');
        Route::put('settings/account/security/password', [PasswordController::class, 'update'])->name('settings.password.update');
        Route::delete('settings/account/security/password', [PasswordController::class, 'destroy'])->name('settings.password.destroy');

        Route::get('settings/account/security/two-factor', [TwoFactorController::class, 'new'])->name('settings.twoFactor.new');
        Route::post('settings/account/security/two-factor', [TwoFactorController::class, 'create'])->name('settings.twoFactor.create');
        Route::delete('settings/account/security/two-factor', [TwoFactorController::class, 'destroy'])->name('settings.twoFactor.destroy');

        Route::post('settings/account/security/recovery-codes', [RecoveryCodeController::class, 'create'])->name('settings.recoveryCodes.create');

        Route::get('settings/account/security/sso/{provider}/redirect', [SsoController::class, 'redirect'])
            ->middleware(['sso', 'throttle:6,1'])
            ->whereIn('provider', SsoProviderEnum::values())
            ->name('settings.sso.redirect');
        Route::delete('settings/account/security/sso', [SsoController::class, 'destroy'])
            ->middleware('throttle:6,1')
            ->name('settings.sso.destroy');
    });

    /*
     * The one screen the recovery codes are ever printed on. It renders what the request that made
     * them flashed and nothing else, so it is empty on its own and needs no gate of its own.
     */
    Route::get('settings/account/security/recovery-codes', [RecoveryCodeController::class, 'show'])->name('settings.recoveryCodes.show');

    Route::middleware(['password.confirm:auth.confirmPassword.new'])->group(function (): void {

        Route::put('settings/account/security/passkeys/{passkey}', [PasskeyController::class, 'update'])->whereNumber('passkey')->name('settings.passkeys.update');

        Route::post('settings/account/security/api-keys', [ApiKeyController::class, 'create'])->name('settings.apiKeys.create');
        Route::delete('settings/account/security/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->whereNumber('apiKey')->name('settings.apiKeys.destroy');
    });

    Route::post('settings/account/profile/photo', [PhotoController::class, 'update'])->name('settings.photo.update');
    Route::delete('settings/account/profile/photo', [PhotoController::class, 'destroy'])->name('settings.photo.destroy');
    Route::get('settings/account/profile/photo/{user}/{size}', [PhotoController::class, 'show'])->whereNumber(['user', 'size'])->name('settings.photo.show');
});

Route::middleware(['administrator'])->group(function (): void {
    Route::get('admin', [OverviewController::class, 'index'])->name('admin.overview.index');

    Route::get('admin/logs', [AdministratorLogController::class, 'index'])->name('admin.logs.index');

    Route::get('admin/moderation', [ModerationController::class, 'index'])->name('admin.moderation.index');
    Route::get('admin/moderation/{version}', [ModerationController::class, 'show'])->whereNumber('version')->name('admin.moderation.show');
    Route::post('admin/moderation/{version}/approval', [ModerationApprovalController::class, 'create'])->whereNumber('version')->name('admin.moderation.approval.create');
    Route::post('admin/moderation/{version}/rejection', [ModerationRejectionController::class, 'create'])->whereNumber('version')->name('admin.moderation.rejection.create');

    Route::get('admin/accounts', [AdminAccountController::class, 'index'])->name('admin.accounts.index');
    Route::get('admin/accounts/{account}', [AdminAccountController::class, 'show'])->whereNumber('account')->name('admin.accounts.show');

    Route::put('admin/accounts/{account}/storage-limit', [AccountStorageLimitController::class, 'update'])->whereNumber('account')->name('admin.accounts.storageLimit.update');
    Route::post('admin/accounts/{account}/lifetime-access', [AccountLifetimeAccessController::class, 'create'])->whereNumber('account')->name('admin.accounts.lifetimeAccess.create');
    Route::delete('admin/accounts/{account}/lifetime-access', [AccountLifetimeAccessController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.lifetimeAccess.destroy');
    Route::post('admin/accounts/{account}/activation', [UserActivationController::class, 'create'])->whereNumber('account')->name('admin.accounts.activation.create');
    Route::delete('admin/accounts/{account}/activation', [UserActivationController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.activation.destroy');

    Route::get('admin/administrators', [AdministratorController::class, 'index'])->name('admin.administrators.index');
    Route::post('admin/administrators', [AdministratorController::class, 'create'])->name('admin.administrators.create');
    Route::delete('admin/administrators/{user}', [AdministratorController::class, 'destroy'])->whereNumber('user')->name('admin.administrators.destroy');
});

if (config('services.telegram.installed') && config('services.telegram.token') !== null) {
    Route::post('telegram/webhook', [TelegramWebhookController::class, 'handleWebhook'])->name('telegram.webhook');
}

require __DIR__.'/auth.php';
