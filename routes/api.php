<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CheckoutSessionController;
use App\Http\Controllers\Api\ContactAvatarController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ContactDuplicateController;
use App\Http\Controllers\Api\ContactDuplicateDismissalController;
use App\Http\Controllers\Api\ContactFavouriteController;
use App\Http\Controllers\Api\ContactMergeController;
use App\Http\Controllers\Api\ContactNameController;
use App\Http\Controllers\Api\ContactTemplateVersionController;
use App\Http\Controllers\Api\EmailSentController;
use App\Http\Controllers\Api\EntityTypeActivationController;
use App\Http\Controllers\Api\EntityTypeController;
use App\Http\Controllers\Api\EntityTypePositionController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldPositionController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MeTimezoneController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PortalSessionController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\SubscriptionCancellationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TemplateActivationController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TemplateDuplicateController;
use App\Http\Controllers\Api\TemplatePositionController;
use App\Http\Controllers\Api\TemplateVersionController;
use App\Http\Controllers\Api\TemplateVersionPageController;
use App\Http\Controllers\Api\TemplateVersionPagePositionController;
use App\Http\Controllers\Api\TemplateVersionPublicationController;
use App\Http\Controllers\Api\TemplateVersionSectionController;
use App\Http\Controllers\Api\TemplateVersionSectionPageController;
use App\Http\Controllers\Api\TemplateVersionSectionPositionController;
use App\Http\Controllers\Api\VaultController;
use Illuminate\Support\Facades\Route;

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

        Route::get('logs', [LogController::class, 'index'])->name('logs');
        Route::get('logs/{log}', [LogController::class, 'show'])->where('log', '[1-9][0-9]*')->name('logs.show');

        Route::get('emails-sent', [EmailSentController::class, 'index'])->name('emailsSent');
        Route::get('emails-sent/{emailSent}', [EmailSentController::class, 'show'])->where('emailSent', '[1-9][0-9]*')->name('emailsSent.show');

        Route::get('vaults', [VaultController::class, 'index'])->name('vaults');
        Route::get('vaults/{vault}', [VaultController::class, 'show'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.show');
        Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
        Route::put('vaults/{vault}', [VaultController::class, 'update'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.update');
        Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.destroy');

        Route::get('entity-types', [EntityTypeController::class, 'index'])->name('entityTypes');
        Route::get('entity-types/{entityType}', [EntityTypeController::class, 'show'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.show');
        Route::post('entity-types', [EntityTypeController::class, 'create'])->name('entityTypes.create');
        Route::put('entity-types/{entityType}', [EntityTypeController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.update');
        Route::delete('entity-types/{entityType}', [EntityTypeController::class, 'destroy'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.destroy');

        Route::put('entity-types/{entityType}/activation', [EntityTypeActivationController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.activation.update');
        Route::put('entity-types/{entityType}/position', [EntityTypePositionController::class, 'update'])->where('entityType', '[0-9a-fA-F-]{36}')->name('entityTypes.position.update');

        Route::get('templates', [TemplateController::class, 'index'])->name('templates');
        Route::get('templates/{template}', [TemplateController::class, 'show'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.show');
        Route::post('templates', [TemplateController::class, 'create'])->name('templates.create');
        Route::put('templates/{template}', [TemplateController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.update');
        Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.destroy');
        Route::put('templates/{template}/activation', [TemplateActivationController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.activation.update');
        Route::put('templates/{template}/position', [TemplatePositionController::class, 'update'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.position.update');
        Route::post('templates/{template}/duplicate', [TemplateDuplicateController::class, 'create'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.duplicate.create');

        Route::get('templates/{template}/versions', [TemplateVersionController::class, 'index'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.versions');
        Route::get('templates/{template}/versions/{version}', [TemplateVersionController::class, 'show'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.show');

        Route::post('templates/{template}/versions', [TemplateVersionController::class, 'create'])->where('template', '[0-9a-fA-F-]{36}')->name('templates.versions.create');
        Route::put('templates/{template}/versions/{version}/publication', [TemplateVersionPublicationController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.publication.update');
        Route::delete('templates/{template}/versions/{version}', [TemplateVersionController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.destroy');

        Route::post('templates/{template}/versions/{version}/pages', [TemplateVersionPageController::class, 'create'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.pages.create');
        Route::put('templates/{template}/versions/{version}/pages/{page}', [TemplateVersionPageController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'page' => '[0-9a-fA-F-]{36}'])->name('templates.versions.pages.update');
        Route::delete('templates/{template}/versions/{version}/pages/{page}', [TemplateVersionPageController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'page' => '[0-9a-fA-F-]{36}'])->name('templates.versions.pages.destroy');
        Route::put('templates/{template}/versions/{version}/pages/{page}/position', [TemplateVersionPagePositionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'page' => '[0-9a-fA-F-]{36}'])->name('templates.versions.pages.position.update');

        Route::post('templates/{template}/versions/{version}/sections', [TemplateVersionSectionController::class, 'create'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*'])->name('templates.versions.sections.create');
        Route::put('templates/{template}/versions/{version}/sections/{section}', [TemplateVersionSectionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.update');
        Route::delete('templates/{template}/versions/{version}/sections/{section}', [TemplateVersionSectionController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.destroy');
        Route::put('templates/{template}/versions/{version}/sections/{section}/position', [TemplateVersionSectionPositionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.position.update');
        Route::put('templates/{template}/versions/{version}/sections/{section}/page', [TemplateVersionSectionPageController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.page.update');

        Route::post('templates/{template}/versions/{version}/sections/{section}/fields', [FieldController::class, 'create'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.create');
        Route::put('templates/{template}/versions/{version}/sections/{section}/fields/{field}', [FieldController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*', 'field' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.update');
        Route::delete('templates/{template}/versions/{version}/sections/{section}/fields/{field}', [FieldController::class, 'destroy'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*', 'field' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.destroy');
        Route::put('templates/{template}/versions/{version}/sections/{section}/fields/{field}/position', [FieldPositionController::class, 'update'])->where(['template' => '[0-9a-fA-F-]{36}', 'version' => '[1-9][0-9]*', 'section' => '[1-9][0-9]*', 'field' => '[1-9][0-9]*'])->name('templates.versions.sections.fields.position.update');

        Route::delete('vaults/{vault}/merges/{merge}', [ContactMergeController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'merge' => '[0-9a-fA-F-]{36}'])->name('merges.destroy');
        Route::get('vaults/{vault}/duplicates', [ContactDuplicateController::class, 'index'])->where('vault', '[0-9a-fA-F-]{36}')->name('duplicates');
        Route::put('vaults/{vault}/duplicates/{candidate}/dismissal', [ContactDuplicateDismissalController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'candidate' => '[1-9][0-9]*'])->name('duplicates.dismissal.update');

        Route::get('vaults/{vault}/contacts', [ContactController::class, 'index'])->where('vault', '[0-9a-fA-F-]{36}')->name('contacts');
        Route::get('vaults/{vault}/contacts/{contact}', [ContactController::class, 'show'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.show');
        Route::post('vaults/{vault}/contacts', [ContactController::class, 'create'])->where('vault', '[0-9a-fA-F-]{36}')->name('contacts.create');
        Route::delete('vaults/{vault}/contacts/{contact}', [ContactController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.destroy');

        Route::get('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'show'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}', 'other' => '[0-9a-fA-F-]{36}'])->name('contacts.merge.show');
        Route::post('vaults/{vault}/contacts/{contact}/merge/{other}', [ContactMergeController::class, 'create'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}', 'other' => '[0-9a-fA-F-]{36}'])->name('contacts.merge.create');

        Route::put('vaults/{vault}/contacts/{contact}/name', [ContactNameController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.name.update');

        Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.favourite.update');
        Route::post('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.avatar.update');
        Route::delete('vaults/{vault}/contacts/{contact}/avatar', [ContactAvatarController::class, 'destroy'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.avatar.destroy');
        Route::put('vaults/{vault}/contacts/{contact}/template-version', [ContactTemplateVersionController::class, 'update'])->where(['vault' => '[0-9a-fA-F-]{36}', 'contact' => '[0-9a-fA-F-]{36}'])->name('contacts.templateVersion.update');
    });
});
