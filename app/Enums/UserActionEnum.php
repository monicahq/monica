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
    case ValueListsSeeded = 'value_lists_seeded';
    case ValueListCreation = 'value_list_created';
    case ValueListUpdate = 'value_list_updated';
    case ValueListPositionUpdate = 'value_list_position_updated';
    case ValueListDeletion = 'value_list_deleted';
    case ValueListItemCreation = 'value_list_item_created';
    case ValueListItemUpdate = 'value_list_item_updated';
    case ValueListItemPositionUpdate = 'value_list_item_position_updated';
    case ValueListItemDisabled = 'value_list_item_disabled';
    case ValueListItemEnabled = 'value_list_item_enabled';
    case ValueListItemDeletion = 'value_list_item_deleted';

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
            self::ValueListsSeeded => 'Set up the value lists the account starts with',
            self::ValueListCreation => 'Created the value list :name',
            self::ValueListUpdate => 'Updated the value list :name',
            self::ValueListPositionUpdate => 'Moved the value list :name',
            self::ValueListDeletion => 'Deleted the value list :name',
            self::ValueListItemCreation => 'Added :label to the value list :name',
            self::ValueListItemUpdate => 'Updated :label in the value list :name',
            self::ValueListItemPositionUpdate => 'Moved :label in the value list :name',
            self::ValueListItemDisabled => 'Stopped offering :label in the value list :name',
            self::ValueListItemEnabled => 'Offered :label in the value list :name again',
            self::ValueListItemDeletion => 'Removed :label from the value list :name',
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
