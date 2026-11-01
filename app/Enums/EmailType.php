<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailType: string
{
    case LoginFailed = 'login_failed';
    case NewLogin = 'new_login';
    case UserIpChanged = 'user_ip_changed';
    case MagicLinkCreated = 'magic_link_created';
    case PasswordResetRequested = 'password_reset_requested';
    case PasswordConfirmationRequested = 'password_confirmation_requested';
    case PasskeyAdded = 'passkey_added';
    case PasskeyRemoved = 'passkey_removed';
    case SecurityKeyAdded = 'security_key_added';
    case SecurityKeyRemoved = 'security_key_removed';
    case PasswordRemoved = 'password_removed';
    case PasswordSet = 'password_set';
    case PasswordChanged = 'password_changed';
    case SignInProviderDisconnected = 'sign_in_provider_disconnected';
    case EmailVerification = 'email_verification';
    case MarketplaceListingApproved = 'marketplace_listing_approved';
    case MarketplaceListingRejected = 'marketplace_listing_rejected';
    case ReminderDue = 'reminder_due';
    case NotificationDigest = 'notification_digest';
    case AccessUnlocked = 'access_unlocked';
    case AccessEnded = 'access_ended';
    case ChannelProofCode = 'channel_proof_code';
    case ChannelDisabled = 'channel_disabled';
    case ChannelTestMessage = 'channel_test_message';
    case AccountExportReady = 'account_export_ready';
    case AccountExportFailed = 'account_export_failed';
    case AccountImportEnded = 'account_import_ended';
}
