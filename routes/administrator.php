<?php

declare(strict_types=1);

use App\Http\Controllers\App\Admin\AccountController;
use App\Http\Controllers\App\Admin\AccountFlagActivationController;
use App\Http\Controllers\App\Admin\AccountLifetimeAccessController;
use App\Http\Controllers\App\Admin\AccountStorageLimitController;
use App\Http\Controllers\App\Admin\AdministratorController;
use App\Http\Controllers\App\Admin\AdministratorLogController;
use App\Http\Controllers\App\Admin\AgentFeedbackController;
use App\Http\Controllers\App\Admin\AgentFeedbackNoteController;
use App\Http\Controllers\App\Admin\AgentFeedbackStatusController;
use App\Http\Controllers\App\Admin\BillingStatusController;
use App\Http\Controllers\App\Admin\ChangelogCommentController;
use App\Http\Controllers\App\Admin\ChangelogCommentHidingController;
use App\Http\Controllers\App\Admin\ChangelogCommentReplyController;
use App\Http\Controllers\App\Admin\ChangelogCommentReviewController;
use App\Http\Controllers\App\Admin\ChangelogVoteController;
use App\Http\Controllers\App\Admin\FlagActivationController;
use App\Http\Controllers\App\Admin\FlagController;
use App\Http\Controllers\App\Admin\InstanceSettingController;
use App\Http\Controllers\App\Admin\ModerationApprovalController;
use App\Http\Controllers\App\Admin\ModerationController;
use App\Http\Controllers\App\Admin\ModerationRejectionController;
use App\Http\Controllers\App\Admin\OverviewController;
use App\Http\Controllers\App\Admin\PreviewListAdmissionController;
use App\Http\Controllers\App\Admin\PreviewListEntryController;
use App\Http\Controllers\App\Admin\PreviewListOldestAdmissionController;
use App\Http\Controllers\App\Admin\SearchEngineController;
use App\Http\Controllers\App\Admin\SearchIndexRebuildController;
use App\Http\Controllers\App\Admin\StripeCatalogueReloadController;
use App\Http\Controllers\App\Admin\SupportAttachmentController;
use App\Http\Controllers\App\Admin\SupportController;
use App\Http\Controllers\App\Admin\SupportNoteController;
use App\Http\Controllers\App\Admin\SupportReplyController;
use App\Http\Controllers\App\Admin\SupportStatusController;
use App\Http\Controllers\App\Admin\TelegramRegistrationCheckController;
use App\Http\Controllers\App\Admin\TelegramRegistrationController;
use App\Http\Controllers\App\Admin\TelegramWebhookSecretController;
use App\Http\Controllers\App\Admin\UserActivationController;
use Illuminate\Support\Facades\Route;
use Laravel\Nightwatch\Http\Middleware\Sample;

Route::middleware(['administrator', Sample::always()])->group(function (): void {
    Route::get('admin', [OverviewController::class, 'index'])->name('admin.overview.index');

    Route::get('admin/logs', [AdministratorLogController::class, 'index'])->name('admin.logs.index');

    Route::get('admin/moderation', [ModerationController::class, 'index'])->name('admin.moderation.index');
    Route::get('admin/moderation/{version}', [ModerationController::class, 'show'])->whereNumber('version')->name('admin.moderation.show');
    Route::post('admin/moderation/{version}/approval', [ModerationApprovalController::class, 'create'])->whereNumber('version')->name('admin.moderation.approval.create');
    Route::post('admin/moderation/{version}/rejection', [ModerationRejectionController::class, 'create'])->whereNumber('version')->name('admin.moderation.rejection.create');

    Route::middleware(['support'])->group(function (): void {
        Route::get('admin/support', [SupportController::class, 'index'])->name('admin.support.index');
        Route::get('admin/support/attachments/{supportAttachment}', [SupportAttachmentController::class, 'show'])->whereUuid('supportAttachment')->name('admin.support.attachments.show');
        Route::get('admin/support/{supportRequest}', [SupportController::class, 'show'])->whereUuid('supportRequest')->name('admin.support.show');
        Route::post('admin/support/{supportRequest}/replies', [SupportReplyController::class, 'create'])->whereUuid('supportRequest')->name('admin.support.replies.create');
        Route::post('admin/support/{supportRequest}/notes', [SupportNoteController::class, 'create'])->whereUuid('supportRequest')->name('admin.support.notes.create');
        Route::put('admin/support/{supportRequest}/status', [SupportStatusController::class, 'update'])->whereUuid('supportRequest')->name('admin.support.status.update');
    });

    // Comments and votes left on What's new, switched on per instance
    Route::middleware(['changelog'])->group(function (): void {
        Route::get('admin/changelog', [ChangelogCommentController::class, 'index'])->name('admin.changelog.index');
        Route::get('admin/changelog/votes', [ChangelogVoteController::class, 'index'])->name('admin.changelog.votes.index');
        Route::post('admin/changelog/comments/{comment}/review', [ChangelogCommentReviewController::class, 'create'])->whereUuid('comment')->name('admin.changelog.comments.review.create');
        Route::post('admin/changelog/comments/{comment}/hiding', [ChangelogCommentHidingController::class, 'create'])->whereUuid('comment')->name('admin.changelog.comments.hiding.create');
        Route::delete('admin/changelog/comments/{comment}/hiding', [ChangelogCommentHidingController::class, 'destroy'])->whereUuid('comment')->name('admin.changelog.comments.hiding.destroy');
        Route::post('admin/changelog/comments/{comment}/replies', [ChangelogCommentReplyController::class, 'create'])->whereUuid('comment')->name('admin.changelog.comments.replies.create');
    });

    Route::get('admin/agent-feedback', [AgentFeedbackController::class, 'index'])->name('admin.agentFeedback.index');
    Route::get('admin/agent-feedback/{agentFeedbackReport}', [AgentFeedbackController::class, 'show'])->whereUuid('agentFeedbackReport')->name('admin.agentFeedback.show');
    Route::post('admin/agent-feedback/{agentFeedbackReport}/notes', [AgentFeedbackNoteController::class, 'create'])->whereUuid('agentFeedbackReport')->name('admin.agentFeedback.notes.create');
    Route::put('admin/agent-feedback/{agentFeedbackReport}/status', [AgentFeedbackStatusController::class, 'update'])->whereUuid('agentFeedbackReport')->name('admin.agentFeedback.status.update');

    Route::get('admin/accounts', [AccountController::class, 'index'])->name('admin.accounts.index');
    Route::get('admin/accounts/{account}', [AccountController::class, 'show'])->whereNumber('account')->name('admin.accounts.show');
    Route::delete('admin/accounts/{account}', [AccountController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.destroy');

    Route::put('admin/accounts/{account}/storage-limit', [AccountStorageLimitController::class, 'update'])->whereNumber('account')->name('admin.accounts.storageLimit.update');
    Route::post('admin/accounts/{account}/lifetime-access', [AccountLifetimeAccessController::class, 'create'])->whereNumber('account')->name('admin.accounts.lifetimeAccess.create');
    Route::delete('admin/accounts/{account}/lifetime-access', [AccountLifetimeAccessController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.lifetimeAccess.destroy');
    Route::post('admin/accounts/{account}/activation', [UserActivationController::class, 'create'])->whereNumber('account')->name('admin.accounts.activation.create');
    Route::delete('admin/accounts/{account}/activation', [UserActivationController::class, 'destroy'])->whereNumber('account')->name('admin.accounts.activation.destroy');
    Route::post('admin/accounts/{account}/flags/{flag}/activation', [AccountFlagActivationController::class, 'create'])->whereNumber('account')->where('flag', '[a-z0-9-]+')->name('admin.accounts.flags.activation.create');
    Route::delete('admin/accounts/{account}/flags/{flag}/activation', [AccountFlagActivationController::class, 'destroy'])->whereNumber('account')->where('flag', '[a-z0-9-]+')->name('admin.accounts.flags.activation.destroy');

    Route::get('admin/preview-list', [PreviewListEntryController::class, 'index'])->name('admin.previewList.index');
    Route::delete('admin/preview-list/{previewListEntry}', [PreviewListEntryController::class, 'destroy'])->whereNumber('previewListEntry')->name('admin.previewList.destroy');
    Route::post('admin/preview-list/{previewListEntry}/admission', [PreviewListAdmissionController::class, 'create'])->whereNumber('previewListEntry')->name('admin.previewList.admission.create');
    Route::post('admin/preview-list/oldest-admissions', [PreviewListOldestAdmissionController::class, 'create'])->name('admin.previewList.oldestAdmissions.create');

    Route::get('admin/flags', [FlagController::class, 'index'])->name('admin.flags.index');

    Route::get('admin/search', [SearchEngineController::class, 'index'])->name('admin.search.index');

    Route::get('admin/billing', [BillingStatusController::class, 'index'])->name('admin.billing.index');
    Route::post('admin/billing/catalogue-reload', [StripeCatalogueReloadController::class, 'create'])->name('admin.billing.catalogueReload.create');

    Route::get('admin/configuration', [InstanceSettingController::class, 'index'])->name('admin.configuration.index');
    Route::post('admin/configuration/telegram/check', [TelegramRegistrationCheckController::class, 'create'])->name('admin.configuration.telegram.check.create');

    /*
     * Changing how the instance behaves costs a password or a passkey, the same gate the security
     * screen puts in front of anything that decides how somebody signs in. The mail transport is
     * reachable from here, and repointing it takes the password reset and sign in links with it.
     */
    Route::middleware(['password.confirm:auth.confirmPassword.new'])->group(function (): void {
        Route::put('admin/configuration/{setting}', [InstanceSettingController::class, 'update'])->name('admin.configuration.update');
        Route::delete('admin/configuration/{setting}', [InstanceSettingController::class, 'destroy'])->name('admin.configuration.destroy');
        Route::delete('admin/configuration/orphans/{name}', [InstanceSettingController::class, 'destroyOrphan'])->name('admin.configuration.orphans.destroy');
        Route::post('admin/configuration/telegram/registration', [TelegramRegistrationController::class, 'create'])->name('admin.configuration.telegram.registration.create');
        Route::post('admin/configuration/telegram/secret', [TelegramWebhookSecretController::class, 'create'])->name('admin.configuration.telegram.secret.create');
        Route::post('admin/flags/{flag}/activation', [FlagActivationController::class, 'create'])->where('flag', '[a-z0-9-]+')->name('admin.flags.activation.create');
        Route::delete('admin/flags/{flag}/activation', [FlagActivationController::class, 'destroy'])->where('flag', '[a-z0-9-]+')->name('admin.flags.activation.destroy');
        Route::post('admin/search/{kind}/rebuild', [SearchIndexRebuildController::class, 'create'])->where('kind', '[a-z-]+')->name('admin.search.rebuild.create');
    });

    Route::get('admin/administrators', [AdministratorController::class, 'index'])->name('admin.administrators.index');
    Route::post('admin/administrators', [AdministratorController::class, 'create'])->name('admin.administrators.create');
    Route::delete('admin/administrators/{user}', [AdministratorController::class, 'destroy'])->whereNumber('user')->name('admin.administrators.destroy');
});
