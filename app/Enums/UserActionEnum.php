<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Translatable;

enum UserActionEnum: string implements Translatable
{
    case AccountCreation = 'account_created';
    case UserProfileUpdate = 'user_profile_updated';
    case UserPhotoUpdate = 'user_photo_updated';
    case UserPhotoDeletion = 'user_photo_deleted';
    case UserPasswordUpdate = 'user_password_updated';
    case UserPreferencesUpdate = 'user_preferences_updated';
    case EmailConfirmation = 'email_confirmed';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor_recovery_codes_regenerated';
    case UserLogin = 'user_logged_in';
    case MagicLinkCreation = 'magic_link_created';
    case ApiKeyCreation = 'api_key_created';
    case ApiKeyDeletion = 'api_key_deleted';
    case VaultCreation = 'vault_created';
    case VaultUpdate = 'vault_updated';
    case VaultPositionUpdate = 'vault_position_updated';
    case VaultDefaultSet = 'vault_default_set';
    case VaultDeletion = 'vault_deleted';
    case VaultRestoration = 'vault_restored';

    /**
     * What the action reads as in the logs, written about whoever performed it.
     * The sentence doubles as the translation key, and its placeholders are
     * filled with the parameters the action was logged with.
     */
    public function description(): string
    {
        return match ($this) {
            self::AccountCreation => 'Created the account',
            self::UserProfileUpdate => 'Updated the profile of :name',
            self::UserPhotoUpdate => 'Changed the photo of :name',
            self::UserPhotoDeletion => 'Removed the photo of :name',
            self::UserPasswordUpdate => 'Changed their password',
            self::UserPreferencesUpdate => 'Changed their preferences',
            self::EmailConfirmation => 'Confirmed their email address',
            self::TwoFactorEnabled => 'Turned two factor authentication on',
            self::TwoFactorDisabled => 'Turned two factor authentication off',
            self::TwoFactorRecoveryCodesRegenerated => 'Asked for new recovery codes',
            self::UserLogin => 'Signed in',
            self::MagicLinkCreation => 'Asked for a sign-in link',
            self::ApiKeyCreation => 'Created the API key called :name',
            self::ApiKeyDeletion => 'Revoked the API key called :name',
            self::VaultCreation => 'Created the vault :name',
            self::VaultUpdate => 'Updated the vault :name',
            self::VaultPositionUpdate => 'Moved the vault :name in the list',
            self::VaultDefaultSet => 'Made :name the vault new records land in',
            self::VaultDeletion => 'Deleted the vault :name',
            self::VaultRestoration => 'Brought the vault :name back',
        };
    }

    /**
     * The sentence this action reads as in the logs.
     *
     * @return list<string>
     */
    public function translationKeys(): array
    {
        return [$this->description()];
    }
}
