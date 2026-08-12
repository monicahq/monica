<?php

declare(strict_types=1);

use App\Http\Controllers\App\GettingStartedController;
use App\Http\Controllers\App\HomeController;
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
use App\Http\Controllers\App\Settings\Account\Security\PasswordController;
use App\Http\Controllers\App\Settings\Account\Security\RecoveryCodeController;
use App\Http\Controllers\App\Settings\Account\Security\SecurityController;
use App\Http\Controllers\App\Settings\Account\Security\TwoFactorController;
use App\Http\Controllers\App\Settings\Account\Templates\FieldController;
use App\Http\Controllers\App\Settings\Account\Templates\FieldPositionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateActivationController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateBuilderController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateCopyController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplatePageController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplatePagePositionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateSectionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateSectionPageController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateSectionPositionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateVersionController;
use App\Http\Controllers\App\Settings\Account\Templates\TemplateVersionPublicationController;
use App\Http\Controllers\App\Settings\SettingsController;
use App\Http\Controllers\App\Vaults\ContactController;
use App\Http\Controllers\App\Vaults\ContactNameController;
use App\Http\Controllers\App\Vaults\ContactSectionController;
use App\Http\Controllers\App\Vaults\FileController;
use App\Http\Controllers\App\Vaults\SetAsideValueController;
use App\Http\Controllers\App\Vaults\VaultController;
use App\Http\Controllers\App\Vaults\VaultDefaultTemplateController;
use App\Http\Controllers\App\Vaults\VaultSettingsController;
use App\ViewModels\Vaults\ContactsViewModel;
use Illuminate\Support\Facades\Route;

// The front door, which sends whoever is signed in wherever their account is up to.
Route::get('/', [HomeController::class, 'index'])->name('home.index');

// The screens where somebody looks after their vaults.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
    Route::get('getting-started', [GettingStartedController::class, 'index'])->name('gettingStarted.index');

    Route::get('vaults', [VaultController::class, 'index'])->name('vaults.index');
    Route::get('vaults/new', [VaultController::class, 'new'])->name('vaults.new');
    Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
    Route::get('vaults/{vault}', [VaultController::class, 'show'])->name('vaults.show');
    Route::put('vaults/{vault}', [VaultController::class, 'update'])->name('vaults.update');
    Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->name('vaults.destroy');

    // What was uploaded onto a record, handed back through the app rather than
    // from a public URL: a document about somebody is as private as the record
    // it sits on.
    Route::get('vaults/{vault}/files/{file}', [FileController::class, 'show'])
        ->whereUuid('file')
        ->name('files.show');

    Route::get('vaults/{vault}/contacts/new', [ContactController::class, 'new'])->name('contacts.new');
    Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->name('contacts.create');

    // Which people the list shows is part of the address rather than a query
    // string, so the chips read as the three places they are. The constraint is
    // what keeps this off `contacts/new`.
    Route::get('vaults/{vault}/contacts/{filter?}', [ContactController::class, 'index'])
        ->whereIn('filter', [ContactsViewModel::FILTER_FAVOURITES, ContactsViewModel::FILTER_ARCHIVED])
        ->name('contacts.index');

    // Which page of the record is open travels in the address the same way, and
    // it is the page's key rather than the page itself: a key belongs to one
    // version of a template, so it cannot be bound to a row on its own.
    //
    // This sits after the list and takes only a uuid, and both halves of that
    // matter. Without the constraint every word the list does not answer to
    // would arrive here as somebody to look up, and postgres refuses to compare
    // a uuid column against a word at all: a mistyped chip would be a 500
    // rather than the 404 it is.
    // Both of these sit above the page route and would be shadowed by a
    // template page whose key slugified to the same word. It is the collision
    // the overview tab already carries and answers the same way: a page called
    // "Set aside" on a record that has values set aside is the least surprising
    // clash available, and naming one of them is already unlikely.
    Route::get('vaults/{vault}/contacts/{contact}/set-aside', [SetAsideValueController::class, 'index'])
        ->whereUuid('contact')
        ->name('contacts.setAside.index');

    // The two things about a contact the template has no say over, on one screen:
    // what they are called, and whether they are on the record at all. The record
    // offers one way in rather than a button for each.
    Route::get('vaults/{vault}/contacts/{contact}/edit', [ContactController::class, 'edit'])
        ->whereUuid('contact')
        ->name('contacts.edit');

    Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])
        ->whereUuid('contact')
        ->name('contacts.name.update');

    Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])
        ->whereUuid('contact')
        ->name('contacts.destroy');

    // A block of the record, open. The record reads until somebody asks for one
    // of these, and which one they asked for is the address rather than a flag
    // held on the client, so cancelling means asking for the record again and
    // therefore genuinely throwing away what was typed.
    //
    // The page is not in the address: the section already knows which page it
    // sits on, and naming it twice is a way for the two halves to disagree.
    Route::get('vaults/{vault}/contacts/{contact}/sections/{templateVersionSection}/edit', [ContactSectionController::class, 'edit'])
        ->whereUuid('contact')
        ->whereNumber('templateVersionSection')
        ->name('contacts.sections.edit');

    Route::put('vaults/{vault}/contacts/{contact}/sections/{templateVersionSection}', [ContactSectionController::class, 'update'])
        ->whereUuid('contact')
        ->whereNumber('templateVersionSection')
        ->name('contacts.sections.update');

    Route::get('vaults/{vault}/contacts/{contact}/{page?}', [ContactController::class, 'show'])
        ->whereUuid('contact')
        ->name('contacts.show');

    Route::get('vaults/{vault}/settings', [VaultSettingsController::class, 'index'])->name('vaults.settings.index');
    Route::put('vaults/{vault}/settings/default-template', [VaultDefaultTemplateController::class, 'update'])->name('vaults.defaultTemplate.update');
});

// The screens where somebody looks after their own account.
Route::middleware(['auth', 'set.locale'])->group(function (): void {
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
    Route::put('settings/account/security/password', [PasswordController::class, 'update'])->name('settings.password.update');

    Route::get('settings/account/security/two-factor', [TwoFactorController::class, 'new'])->name('settings.twoFactor.new');
    Route::post('settings/account/security/two-factor', [TwoFactorController::class, 'create'])->name('settings.twoFactor.create');
    Route::delete('settings/account/security/two-factor', [TwoFactorController::class, 'destroy'])->name('settings.twoFactor.destroy');

    Route::post('settings/account/security/recovery-codes', [RecoveryCodeController::class, 'create'])->name('settings.recoveryCodes.create');

    Route::post('settings/account/security/api-keys', [ApiKeyController::class, 'create'])->name('settings.apiKeys.create');
    Route::delete('settings/account/security/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->whereNumber('apiKey')->name('settings.apiKeys.destroy');

    Route::post('settings/account/profile/photo', [PhotoController::class, 'update'])->name('settings.photo.update');
    Route::delete('settings/account/profile/photo', [PhotoController::class, 'destroy'])->name('settings.photo.destroy');
    Route::get('settings/account/profile/photo/{user}/{size}', [PhotoController::class, 'show'])->whereNumber(['user', 'size'])->name('settings.photo.show');
});
