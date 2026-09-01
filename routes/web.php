<?php

declare(strict_types=1);

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
use App\Http\Controllers\App\Settings\Account\AccountController;
use App\Http\Controllers\App\Settings\Account\DefaultValues\ValueListController;
use App\Http\Controllers\App\Settings\Account\DefaultValues\ValueListItemActivationController;
use App\Http\Controllers\App\Settings\Account\DefaultValues\ValueListItemController;
use App\Http\Controllers\App\Settings\Account\DefaultValues\ValueListItemPositionController;
use App\Http\Controllers\App\Settings\Account\EntityTypes\EntityTypeController;
use App\Http\Controllers\App\Settings\Account\EntityTypes\EntityTypePositionController;
use App\Http\Controllers\App\Settings\Account\Logs\EmailSentController;
use App\Http\Controllers\App\Settings\Account\Logs\LogController;
use App\Http\Controllers\App\Settings\Account\Preferences\PreferenceController;
use App\Http\Controllers\App\Settings\Account\Profile\PhotoController;
use App\Http\Controllers\App\Settings\Account\Profile\ProfileController;
use App\Http\Controllers\App\Settings\Account\Security\ApiKeyController;
use App\Http\Controllers\App\Settings\Account\Security\PasskeyController;
use App\Http\Controllers\App\Settings\Account\Security\PasswordController;
use App\Http\Controllers\App\Settings\Account\Security\RecoveryCodeController;
use App\Http\Controllers\App\Settings\Account\Security\SecurityController;
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
use App\Http\Controllers\App\Settings\Account\Templates\TemplateVersionPublicationController;
use App\Http\Controllers\App\Settings\SettingsController;
use App\Http\Controllers\App\SharedContactController;
use App\Http\Controllers\App\SharedContactPasswordController;
use App\Http\Controllers\App\Vaults\ContactAvatarController;
use App\Http\Controllers\App\Vaults\ContactController;
use App\Http\Controllers\App\Vaults\ContactFavouriteController;
use App\Http\Controllers\App\Vaults\ContactNameController;
use App\Http\Controllers\App\Vaults\ContactSectionController;
use App\Http\Controllers\App\Vaults\ContactShareLinkController;
use App\Http\Controllers\App\Vaults\ContactTemplateVersionController;
use App\Http\Controllers\App\Vaults\FileController;
use App\Http\Controllers\App\Vaults\SetAsideValueController;
use App\Http\Controllers\App\Vaults\VaultController;
use App\Http\Controllers\App\Vaults\VaultDefaultTemplateController;
use App\Http\Controllers\App\Vaults\VaultSettingsController;
use App\ViewModels\Vaults\ContactsViewModel;
use Illuminate\Support\Facades\Route;

// Shows nothing of its own: signed in goes wherever the account is up to, everybody
// else to the sign in screen.
Route::get('/', [HomeController::class, 'index'])->name('home.index');

// The api reference, open to anybody, signed in or not.
Route::get('docs/api', [ApiDocsController::class, 'index'])
    ->name('docs.api.index');

// The same reference as plain text, whole and one endpoint at a time.
Route::get('docs/api.md', [ApiDocsMarkdownController::class, 'index'])
    ->name('docs.api.markdown.index');

Route::get('docs/api/{section}.md', [ApiDocsMarkdownController::class, 'show'])
    ->where('section', '[a-z0-9\-]+')
    ->name('docs.api.markdown.show');

// One endpoint to a page, so a link to one of them opens that one.
Route::get('docs/api/{section}', [ApiDocsController::class, 'show'])
    ->where('section', '[a-z0-9\-]+')
    ->name('docs.api.show');

// Public on purpose: a link to something published here has to open for somebody
// who never signed up. The facets combine freely, so they travel in the query string.
Route::get('marketplace', [MarketplaceController::class, 'index'])
    ->name('marketplace.index');

// Public by design: whoever opens it has no account here and never gets one. Only a
// hash of the token is stored, so there is no binding, and the pattern keeps a
// mistyped address a 404. Throttled, because the link is a secret in an address.
Route::get('s/{token}/{page?}', [SharedContactController::class, 'show'])
    ->middleware(['throttle:60,1'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->name('share.show');

Route::post('s/{token}', [SharedContactPasswordController::class, 'create'])
    ->middleware(['throttle:20,1'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->name('share.password.create');

// The screens where somebody looks after their vaults.
Route::middleware(['auth', 'active', 'verified:auth.verification.notice'])->group(function (): void {
    Route::get('getting-started', [GettingStartedController::class, 'index'])->name('gettingStarted.index');

    Route::get('vaults', [VaultController::class, 'index'])->name('vaults.index');
    Route::get('vaults/new', [VaultController::class, 'new'])->name('vaults.new');
    Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
    Route::get('vaults/{vault}', [VaultController::class, 'show'])->name('vaults.show');
    Route::put('vaults/{vault}', [VaultController::class, 'update'])->name('vaults.update');
    Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->name('vaults.destroy');

    // Served through the app: a document is as private as the record it sits on.
    Route::get('vaults/{vault}/files/{file}', [FileController::class, 'show'])
        ->whereUuid('file')
        ->name('files.show');

    Route::get('vaults/{vault}/contacts/new', [ContactController::class, 'new'])->name('contacts.new');
    Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->name('contacts.create');

    // The filter is in the address so the chips read as places. The constraint keeps
    // this route off `contacts/new`.
    Route::get('vaults/{vault}/contacts/{filter?}', [ContactController::class, 'index'])
        ->whereIn('filter', [ContactsViewModel::FILTER_FAVOURITES, ContactsViewModel::FILTER_ARCHIVED])
        ->name('contacts.index');

    // Above the page route, so a template page keyed the same word is shadowed. The
    // uuid constraint keeps a mistyped word a 404: postgres will not compare a uuid
    // column against a word, so without it that is a 500.
    Route::get('vaults/{vault}/contacts/{contact}/set-aside', [SetAsideValueController::class, 'index'])
        ->whereUuid('contact')
        ->name('contacts.setAside.index');

    // One screen for what the template has no say over: name, picture, presence.
    Route::get('vaults/{vault}/contacts/{contact}/edit', [ContactController::class, 'edit'])
        ->whereUuid('contact')
        ->name('contacts.edit');

    Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])
        ->whereUuid('contact')
        ->name('contacts.name.update');

    Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])
        ->whereUuid('contact')
        ->name('contacts.avatar.update');

    Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])
        ->whereUuid('contact')
        ->name('contacts.avatar.destroy');

    Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])
        ->whereUuid('contact')
        ->name('contacts.favourite.update');

    Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])
        ->whereUuid('contact')
        ->name('contacts.destroy');

    // Onto the published version, one record at a time. Publishing moves nobody by
    // itself, and nothing travels in the request: the template says where it can go.
    Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactTemplateVersionController::class, 'update'])
        ->whereUuid('contact')
        ->name('contacts.templateVersion.update');

    // Which block is open is the address rather than client state, so cancelling
    // really throws away what was typed. The page is left out: the section knows it.
    Route::get('vaults/{vault}/contacts/{contact}/sections/{templateVersionSection}/edit', [ContactSectionController::class, 'edit'])
        ->whereUuid('contact')
        ->whereNumber('templateVersionSection')
        ->name('contacts.sections.edit');

    Route::put('vaults/{vault}/contacts/{contact}/sections/{templateVersionSection}', [ContactSectionController::class, 'update'])
        ->whereUuid('contact')
        ->whereNumber('templateVersionSection')
        ->name('contacts.sections.update');

    // Above the page route with the others. A link is addressed by its uuid: the token
    // that opens it would otherwise sit in the address of the screen managing it.
    Route::get('vaults/{vault}/contacts/{contact}/share-links', [ContactShareLinkController::class, 'index'])
        ->whereUuid('contact')
        ->name('contacts.shareLinks.index');

    Route::get('vaults/{vault}/contacts/{contact}/share-links/new', [ContactShareLinkController::class, 'new'])
        ->whereUuid('contact')
        ->name('contacts.shareLinks.new');

    Route::post('vaults/{vault}/contacts/{contact}/share-links', [ContactShareLinkController::class, 'create'])
        ->whereUuid('contact')
        ->name('contacts.shareLinks.create');

    Route::get('vaults/{vault}/contacts/{contact}/share-links/{shareLink}', [ContactShareLinkController::class, 'show'])
        ->whereUuid('contact')
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.show');

    Route::get('vaults/{vault}/contacts/{contact}/share-links/{shareLink}/edit', [ContactShareLinkController::class, 'edit'])
        ->whereUuid('contact')
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.edit');

    Route::put('vaults/{vault}/contacts/{contact}/share-links/{shareLink}', [ContactShareLinkController::class, 'update'])
        ->whereUuid('contact')
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.update');

    // Revoking keeps the row: a link that is gone is still part of who saw this page.
    Route::delete('vaults/{vault}/contacts/{contact}/share-links/{shareLink}', [ContactShareLinkController::class, 'destroy'])
        ->whereUuid('contact')
        ->whereUuid('shareLink')
        ->name('contacts.shareLinks.destroy');

    Route::get('vaults/{vault}/contacts/{contact}/{page?}', [ContactController::class, 'show'])
        ->whereUuid('contact')
        ->name('contacts.show');

    Route::get('vaults/{vault}/settings', [VaultSettingsController::class, 'index'])->name('vaults.settings.index');
    Route::put('vaults/{vault}/settings/default-template', [VaultDefaultTemplateController::class, 'update'])->name('vaults.defaultTemplate.update');

    // The screens where somebody looks after their own account.
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::get('settings/account/profile', [ProfileController::class, 'index'])->name('settings.profile.index');
    Route::put('settings/account/profile', [ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/account', [AccountController::class, 'destroy'])->name('settings.account.destroy');
    Route::get('settings/account/logs', [LogController::class, 'index'])->name('settings.logs.index');
    Route::get('settings/account/logs/emails', [EmailSentController::class, 'index'])->name('settings.emailsSent.index');

    Route::get('settings/account/preferences', [PreferenceController::class, 'index'])->name('settings.preferences.index');
    Route::put('settings/account/preferences', [PreferenceController::class, 'update'])->name('settings.preferences.update');

    Route::get('settings/account/default-values', [ValueListController::class, 'index'])->name('settings.valueLists.index');
    Route::post('settings/account/default-values', [ValueListController::class, 'create'])->name('settings.valueLists.create');
    Route::put('settings/account/default-values/{valueList}', [ValueListController::class, 'update'])->whereNumber('valueList')->name('settings.valueLists.update');
    Route::delete('settings/account/default-values/{valueList}', [ValueListController::class, 'destroy'])->whereNumber('valueList')->name('settings.valueLists.destroy');

    Route::post('settings/account/default-values/{valueList}/values', [ValueListItemController::class, 'create'])->whereNumber('valueList')->name('settings.valueListItems.create');
    Route::put('settings/account/default-values/values/{valueListItem}', [ValueListItemController::class, 'update'])->whereNumber('valueListItem')->name('settings.valueListItems.update');
    Route::delete('settings/account/default-values/values/{valueListItem}', [ValueListItemController::class, 'destroy'])->whereNumber('valueListItem')->name('settings.valueListItems.destroy');
    Route::put('settings/account/default-values/values/{valueListItem}/position', [ValueListItemPositionController::class, 'update'])->whereNumber('valueListItem')->name('settings.valueListItemPosition.update');
    Route::put('settings/account/default-values/values/{valueListItem}/active', [ValueListItemActivationController::class, 'update'])->whereNumber('valueListItem')->name('settings.valueListItemActivation.update');

    Route::get('settings/account/entity-types', [EntityTypeController::class, 'index'])->name('settings.entityTypes.index');
    Route::get('settings/account/entity-types/new', [EntityTypeController::class, 'new'])->name('settings.entityTypes.new');
    Route::post('settings/account/entity-types', [EntityTypeController::class, 'create'])->name('settings.entityTypes.create');
    Route::get('settings/account/entity-types/{entityType}/edit', [EntityTypeController::class, 'edit'])->name('settings.entityTypes.edit');
    Route::put('settings/account/entity-types/{entityType}', [EntityTypeController::class, 'update'])->name('settings.entityTypes.update');
    Route::delete('settings/account/entity-types/{entityType}', [EntityTypeController::class, 'destroy'])->name('settings.entityTypes.destroy');
    Route::put('settings/account/entity-types/{entityType}/position', [EntityTypePositionController::class, 'update'])->name('settings.entityTypePosition.update');

    Route::get('settings/account/templates', [TemplateController::class, 'index'])->name('settings.templates.index');
    Route::get('settings/account/templates/new', [TemplateController::class, 'new'])->name('settings.templates.new');
    Route::post('settings/account/templates', [TemplateController::class, 'create'])->name('settings.templates.create');
    Route::get('settings/account/templates/{template}/edit', [TemplateController::class, 'edit'])->name('settings.templates.edit');
    Route::put('settings/account/templates/{template}', [TemplateController::class, 'update'])->name('settings.templates.update');
    Route::delete('settings/account/templates/{template}', [TemplateController::class, 'destroy'])->name('settings.templates.destroy');
    Route::post('settings/account/templates/{template}/copy', [TemplateCopyController::class, 'create'])->name('settings.templateCopy.create');
    Route::put('settings/account/templates/{template}/active', [TemplateActivationController::class, 'update'])->name('settings.templateActivation.update');

    // `export` is the screen and `export/file` the bytes; `import/check` reads a file
    // and writes nothing. Each gets its own address, so what leaves or arrives can be
    // read first, and the check is never carried into the import. Sections hang under
    // a template, which is what pins the version.
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
    Route::post('settings/account/security/password', [PasswordController::class, 'create'])->name('settings.password.create');
    Route::put('settings/account/security/password', [PasswordController::class, 'update'])->name('settings.password.update');
    Route::delete('settings/account/security/password', [PasswordController::class, 'destroy'])->name('settings.password.destroy');

    Route::get('settings/account/security/two-factor', [TwoFactorController::class, 'new'])->name('settings.twoFactor.new');
    Route::post('settings/account/security/two-factor', [TwoFactorController::class, 'create'])->name('settings.twoFactor.create');
    Route::delete('settings/account/security/two-factor', [TwoFactorController::class, 'destroy'])->name('settings.twoFactor.destroy');

    Route::post('settings/account/security/recovery-codes', [RecoveryCodeController::class, 'create'])->name('settings.recoveryCodes.create');

    // Adding a passkey and signing in with one belong to the package, which
    // fires events this application listens to. It fires nothing on a rename or
    // a deletion, so those two are ours.
    Route::put('settings/account/security/passkeys/{passkey}', [PasskeyController::class, 'update'])->whereNumber('passkey')->name('settings.passkeys.update');
    Route::delete('settings/account/security/passkeys/{passkey}', [PasskeyController::class, 'destroy'])->whereNumber('passkey')->name('settings.passkeys.destroy');

    Route::post('settings/account/security/api-keys', [ApiKeyController::class, 'create'])->name('settings.apiKeys.create');
    Route::delete('settings/account/security/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->whereNumber('apiKey')->name('settings.apiKeys.destroy');

    Route::post('settings/account/profile/photo', [PhotoController::class, 'update'])->name('settings.photo.update');
    Route::delete('settings/account/profile/photo', [PhotoController::class, 'destroy'])->name('settings.photo.destroy');
    Route::get('settings/account/profile/photo/{user}/{size}', [PhotoController::class, 'show'])->whereNumber(['user', 'size'])->name('settings.photo.show');
});

// The admin space, for whoever runs the instance.
//
// No `auth` on purpose: its redirect would confirm the address is real. The
// middleware answers a flat 404 to a guest, to a user without the capacity and to an
// inactive holder alike. Search and filters travel in the query string, so a lookup
// can be sent to a colleague.
Route::middleware(['administrator'])->group(function (): void {
    // The root of the area: how the instance is doing before anything is done to it.
    Route::get('admin', [OverviewController::class, 'index'])->name('admin.overview.index');

    Route::get('admin/logs', [AdministratorLogController::class, 'index'])->name('admin.logs.index');

    // A decision is a POST onto a noun rather than a PATCH: approving and refusing
    // must not be read off one payload.
    Route::get('admin/moderation', [ModerationController::class, 'index'])->name('admin.moderation.index');
    Route::get('admin/moderation/{version}', [ModerationController::class, 'show'])->whereNumber('version')->name('admin.moderation.show');
    Route::post('admin/moderation/{version}/approval', [ModerationApprovalController::class, 'create'])->whereNumber('version')->name('admin.moderation.approval.create');
    Route::post('admin/moderation/{version}/rejection', [ModerationRejectionController::class, 'create'])->whereNumber('version')->name('admin.moderation.rejection.create');

    // Accounts only. No route here takes a vault, a contact or a file, which is what
    // keeps everything written inside a vault unreachable from here.
    Route::get('admin/accounts', [AdminAccountController::class, 'index'])->name('admin.accounts.index');
    Route::get('admin/accounts/{account}', [AdminAccountController::class, 'show'])->whereNumber('account')->name('admin.accounts.show');

    // What support needs. Each sits behind a warning and is written down against the
    // administrator who did it.
    Route::put('admin/accounts/{account}/storage-limit', [AccountStorageLimitController::class, 'update'])->whereNumber('account')->name('admin.accounts.storageLimit.update');
    Route::post('admin/accounts/{account}/lifetime-access', [AccountLifetimeAccessController::class, 'create'])->whereNumber('account')->name('admin.accounts.lifetimeAccess.create');
    Route::delete('admin/accounts/{account}/lifetime-access', [AccountLifetimeAccessController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.lifetimeAccess.destroy');
    Route::post('admin/accounts/{account}/activation', [UserActivationController::class, 'create'])->whereNumber('account')->name('admin.accounts.activation.create');
    Route::delete('admin/accounts/{account}/activation', [UserActivationController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.activation.destroy');

    // The grant takes an email address: the operator already knows who they are
    // promoting, rather than going looking in a list.
    Route::get('admin/administrators', [AdministratorController::class, 'index'])->name('admin.administrators.index');
    Route::post('admin/administrators', [AdministratorController::class, 'create'])->name('admin.administrators.create');
    Route::delete('admin/administrators/{user}', [AdministratorController::class, 'destroy'])->whereNumber('user')->name('admin.administrators.destroy');
});
