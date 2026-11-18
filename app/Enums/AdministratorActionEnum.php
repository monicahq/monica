<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;
use Override;

enum AdministratorActionEnum: string implements Translatable
{
    case MarketplaceVersionApproved = 'marketplace_version_approved';
    case MarketplaceVersionRejected = 'marketplace_version_rejected';
    case UserDeactivated = 'user_deactivated';
    case UserReactivated = 'user_reactivated';
    case AccountStorageLimitUpdated = 'account_storage_limit_updated';
    case LifetimeAccessGranted = 'lifetime_access_granted';
    case LifetimeAccessWithdrawn = 'lifetime_access_withdrawn';
    case AdministratorAccessGranted = 'administrator_access_granted';
    case AdministratorAccessWithdrawn = 'administrator_access_withdrawn';
    case InstanceSettingUpdated = 'instance_setting_updated';
    case InstanceSettingReverted = 'instance_setting_reverted';
    case TelegramWebhookRegistered = 'telegram_webhook_registered';
    case FlagActivated = 'flag_activated';
    case FlagDeactivated = 'flag_deactivated';
    case FlagActivatedForEveryAccount = 'flag_activated_for_every_account';
    case FlagDeactivatedForEveryAccount = 'flag_deactivated_for_every_account';
    case SupportReplySent = 'support_reply_sent';
    case SupportNoteAdded = 'support_note_added';
    case SupportRequestStatusChanged = 'support_request_status_changed';
    case SearchIndexRebuildStarted = 'search_index_rebuild_started';
    case StripeCatalogueReloaded = 'stripe_catalogue_reloaded';
    case PreviewListEntryAdmitted = 'preview_list_entry_admitted';
    case PreviewListEntryRemoved = 'preview_list_entry_removed';
    case ChangelogCommentReviewed = 'changelog_comment_reviewed';
    case ChangelogCommentHidden = 'changelog_comment_hidden';
    case ChangelogCommentShown = 'changelog_comment_shown';
    case ChangelogCommentReplied = 'changelog_comment_replied';

    public function description(): string
    {
        return match ($this) {
            self::MarketplaceVersionApproved => 'Approved version :version of :name',
            self::MarketplaceVersionRejected => 'Rejected version :version of :name',
            self::UserDeactivated => 'Deactivated :name',
            self::UserReactivated => 'Reactivated :name',
            self::AccountStorageLimitUpdated => 'Changed the storage limit of the account of :name',
            self::LifetimeAccessGranted => 'Granted lifetime access to the account of :name',
            self::LifetimeAccessWithdrawn => 'Withdrew lifetime access from the account of :name',
            self::AdministratorAccessGranted => 'Made :name an administrator',
            self::AdministratorAccessWithdrawn => 'Withdrew administrator access from :name',
            self::InstanceSettingUpdated => 'Changed the instance setting :name',
            self::InstanceSettingReverted => 'Put the instance setting :name back to what the deployment supplies',
            self::TelegramWebhookRegistered => 'Registered the Telegram bot at :url',
            self::FlagActivated => 'Switched on :flag for :name',
            self::FlagDeactivated => 'Switched off :flag for :name',
            self::FlagActivatedForEveryAccount => 'Switched on :flag for every account',
            self::FlagDeactivatedForEveryAccount => 'Switched off :flag for every account',
            self::SupportReplySent => 'Replied to the support request :reference of :name',
            self::SupportNoteAdded => 'Left an internal note on the support request :reference of :name',
            self::SupportRequestStatusChanged => 'Moved the support request :reference of :name to :status',
            self::SearchIndexRebuildStarted => 'Started the rebuild of the search index :index',
            self::StripeCatalogueReloaded => 'Read the Stripe catalogue again',
            self::PreviewListEntryAdmitted => 'Admitted :email from the waiting list',
            self::PreviewListEntryRemoved => 'Removed :email from the waiting list',
            self::ChangelogCommentReviewed => 'Reviewed the comment of :name on “:title”',
            self::ChangelogCommentHidden => 'Hid the comment of :name on “:title” from What’s new',
            self::ChangelogCommentShown => 'Showed the comment of :name on “:title” on What’s new again',
            self::ChangelogCommentReplied => 'Replied to the comment of :name on “:title”',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MarketplaceVersionApproved, self::MarketplaceVersionRejected => 'tasks',
            self::UserDeactivated, self::UserReactivated => 'profile',
            self::AccountStorageLimitUpdated => 'layers',
            self::LifetimeAccessGranted, self::LifetimeAccessWithdrawn => 'star',
            self::AdministratorAccessGranted, self::AdministratorAccessWithdrawn => 'key',
            self::InstanceSettingUpdated, self::InstanceSettingReverted, self::TelegramWebhookRegistered => 'settings',
            self::FlagActivated, self::FlagDeactivated, self::FlagActivatedForEveryAccount, self::FlagDeactivatedForEveryAccount => 'tag',
            self::SupportReplySent, self::SupportNoteAdded, self::SupportRequestStatusChanged => 'mail',
            self::SearchIndexRebuildStarted => 'search',
            self::StripeCatalogueReloaded => 'credit-card',
            self::PreviewListEntryAdmitted, self::PreviewListEntryRemoved => 'profile',
            self::ChangelogCommentReviewed, self::ChangelogCommentHidden, self::ChangelogCommentShown, self::ChangelogCommentReplied => 'journal',
        };
    }

    public function namesTheAccountHolder(): bool
    {
        return match ($this) {
            self::UserDeactivated,
            self::UserReactivated,
            self::AccountStorageLimitUpdated,
            self::LifetimeAccessGranted,
            self::LifetimeAccessWithdrawn,
            self::AdministratorAccessGranted,
            self::AdministratorAccessWithdrawn,
            self::FlagActivated,
            self::FlagDeactivated,
            self::SupportReplySent,
            self::SupportNoteAdded,
            self::SupportRequestStatusChanged,
            self::ChangelogCommentReviewed,
            self::ChangelogCommentHidden,
            self::ChangelogCommentShown,
            self::ChangelogCommentReplied => true,
            self::MarketplaceVersionApproved,
            self::MarketplaceVersionRejected,
            self::InstanceSettingUpdated,
            self::InstanceSettingReverted,
            self::TelegramWebhookRegistered,
            self::FlagActivatedForEveryAccount,
            self::FlagDeactivatedForEveryAccount,
            self::SearchIndexRebuildStarted,
            self::StripeCatalogueReloaded,
            self::PreviewListEntryAdmitted,
            self::PreviewListEntryRemoved => false,
        };
    }

    /** @return list<string> */
    #[Override]
    public function translationKeys(): array
    {
        return [$this->description()];
    }
}
