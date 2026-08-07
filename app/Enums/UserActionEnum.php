<?php

declare(strict_types=1);

namespace App\Enums;

enum UserActionEnum: string
{
    case AccountCreation = 'account_created';
    case UserInformationUpdate = 'user_information_updated';
    case UserProfileUpdate = 'user_profile_updated';
    case UserPhotoUpdate = 'user_photo_updated';
    case UserPhotoDeletion = 'user_photo_deleted';
    case UserPasswordUpdate = 'user_password_updated';
    case UserPreferencesUpdate = 'user_preferences_updated';
    case UserDeletion = 'user_deleted';
    case EmailConfirmation = 'email_confirmed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor_recovery_codes_regenerated';
    case UserLogin = 'user_logged_in';
    case MagicLinkCreation = 'magic_link_created';
    case ApiKeyCreation = 'api_key_created';
    case ApiKeyDeletion = 'api_key_deleted';

    /**
     * What the action reads as in the logs, written about whoever performed it.
     * The sentence doubles as the translation key, and its placeholders are
     * filled with the parameters the action was logged with.
     */
    public function description(): string
    {
        return match ($this) {
            self::AccountCreation => 'Created the account',
            self::UserInformationUpdate => 'Updated the account of :email',
            self::UserProfileUpdate => 'Updated the profile of :name',
            self::UserPhotoUpdate => 'Changed the photo of :name',
            self::UserPhotoDeletion => 'Removed the photo of :name',
            self::UserPasswordUpdate => 'Changed their password',
            self::UserPreferencesUpdate => 'Changed their preferences',
            self::UserDeletion => 'Deleted the account of :email',
            self::EmailConfirmation => 'Confirmed their email address',
            self::TwoFactorEnabled => 'Turned two factor authentication on',
            self::TwoFactorDisabled => 'Turned two factor authentication off',
            self::TwoFactorRecoveryCodesRegenerated => 'Asked for new recovery codes',
            self::UserLogin => 'Signed in',
            self::MagicLinkCreation => 'Asked for a sign-in link',
            self::ApiKeyCreation => 'Created the API key called :name',
            self::ApiKeyDeletion => 'Revoked the API key called :name',
        };
    }
}
