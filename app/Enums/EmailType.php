<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailType: string
{
    case LoginFailed = 'login_failed';
    case NewLogin = 'new_login';
    case UserIpChanged = 'user_ip_changed';
    case MagicLinkCreated = 'magic_link_created';
    case PasskeyAdded = 'passkey_added';
    case PasskeyRemoved = 'passkey_removed';
    case PasswordRemoved = 'password_removed';
    case PasswordSet = 'password_set';
    case EmailVerification = 'email_verification';
    case MarketplaceListingApproved = 'marketplace_listing_approved';
    case MarketplaceListingRejected = 'marketplace_listing_rejected';
    case ReminderDue = 'reminder_due';
    case NotificationDigest = 'notification_digest';
    case AccessUnlocked = 'access_unlocked';
    case AccessEnded = 'access_ended';
}
