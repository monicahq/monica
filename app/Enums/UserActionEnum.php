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
    case VaultDefaultTemplateSet = 'vault_default_template_set';
    case VaultDefaultTemplateRemoved = 'vault_default_template_removed';
    case FileUpload = 'file_uploaded';
    case FileDeletion = 'file_deleted';
    case OrphanedFilesSwept = 'orphaned_files_swept';
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
    case EntityTypeCreation = 'entity_type_created';
    case EntityTypeUpdate = 'entity_type_updated';
    case EntityTypePositionUpdate = 'entity_type_position_updated';
    case EntityTypeActivated = 'entity_type_activated';
    case EntityTypeDeactivated = 'entity_type_deactivated';
    case EntityTypeDeletion = 'entity_type_deleted';
    case DefaultTemplateSeeded = 'default_template_seeded';
    case TemplateCreation = 'template_created';
    case TemplateUpdate = 'template_updated';
    case TemplateDuplication = 'template_duplicated';
    case TemplatePositionUpdate = 'template_position_updated';
    case TemplateActivated = 'template_activated';
    case TemplateDeactivated = 'template_deactivated';
    case TemplateDeletion = 'template_deleted';
    case TemplateVersionDrafted = 'template_version_drafted';
    case TemplateVersionPublished = 'template_version_published';
    case TemplateVersionRetired = 'template_version_retired';
    case TemplateVersionDeletion = 'template_version_deleted';
    case SectionCreation = 'section_created';
    case SectionUpdate = 'section_updated';
    case SectionActivated = 'section_activated';
    case SectionDeactivated = 'section_deactivated';
    case SectionDeletion = 'section_deleted';
    case SectionVersionDrafted = 'section_version_drafted';
    case SectionVersionPublished = 'section_version_published';
    case SectionVersionRetired = 'section_version_retired';
    case SectionVersionDeletion = 'section_version_deleted';
    case TemplatePageCreation = 'template_page_created';
    case TemplatePageUpdate = 'template_page_updated';
    case TemplatePagePositionUpdate = 'template_page_position_updated';
    case TemplatePageDeletion = 'template_page_deleted';
    case SectionAddedToTemplate = 'section_added_to_template';
    case SectionRemovedFromTemplate = 'section_removed_from_template';
    case SectionMovedToPage = 'section_moved_to_page';
    case TemplateSectionUpdate = 'template_section_updated';
    case TemplateSectionPositionUpdate = 'template_section_position_updated';
    case FieldCreation = 'field_created';
    case FieldUpdate = 'field_updated';
    case FieldPositionUpdate = 'field_position_updated';
    case FieldDeletion = 'field_deleted';
    case ContactCreation = 'contact_created';
    case ContactNameUpdate = 'contact_name_updated';
    case ContactAvatarUpdate = 'contact_avatar_updated';
    case ContactAvatarDeletion = 'contact_avatar_deleted';
    case ContactArchived = 'contact_archived';
    case ContactUnarchived = 'contact_unarchived';
    case ContactFavourited = 'contact_favourited';
    case ContactUnfavourited = 'contact_unfavourited';
    case ContactDeletion = 'contact_deleted';
    case ContactRestoration = 'contact_restored';
    case EntityCreation = 'entity_created';
    case EntityNameUpdate = 'entity_name_updated';
    case EntityArchived = 'entity_archived';
    case EntityUnarchived = 'entity_unarchived';
    case EntityDeletion = 'entity_deleted';
    case EntityRestoration = 'entity_restored';
    case FieldValueRowCreation = 'field_value_row_created';
    case FieldValueRowPositionUpdate = 'field_value_row_position_updated';
    case FieldValueRowDeletion = 'field_value_row_deleted';
    case FieldValueSet = 'field_value_set';
    case FieldValueCleared = 'field_value_cleared';
    case FieldValuesDetached = 'field_values_detached';
    case FieldValuesReattached = 'field_values_reattached';
    case RecordSnapshotTaken = 'record_snapshot_taken';
    case RecordSnapshotRestored = 'record_snapshot_restored';
    case RecordMovedToTemplateVersion = 'record_moved_to_template_version';
    case MarketplaceListingCreation = 'marketplace_listing_created';
    case MarketplaceListingUpdate = 'marketplace_listing_updated';
    case MarketplaceListingSubmission = 'marketplace_listing_submitted';
    case MarketplaceListingWithdrawal = 'marketplace_listing_withdrawn';

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
            self::FileUpload => 'Uploaded the file :name',
            self::FileDeletion => 'Deleted the file :name',
            self::OrphanedFilesSwept => 'Removed :count files nothing pointed at any more',
            self::VaultDefaultTemplateSet => 'Made :name the template new records start from here',
            self::VaultDefaultTemplateRemoved => 'Stopped starting new records from :name here',
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
            self::EntityTypeCreation => 'Created the kind of record :name',
            self::EntityTypeUpdate => 'Updated the kind of record :name',
            self::EntityTypePositionUpdate => 'Moved the kind of record :name',
            self::EntityTypeActivated => 'Allowed new :name to be created again',
            self::EntityTypeDeactivated => 'Stopped new :name being created',
            self::EntityTypeDeletion => 'Deleted the kind of record :name',
            self::DefaultTemplateSeeded => 'Set up the template :name the account starts with',
            self::TemplateCreation => 'Created the template :name',
            self::TemplateUpdate => 'Updated the template :name',
            self::TemplateDuplication => 'Copied the template :source into :name',
            self::TemplatePositionUpdate => 'Moved the template :name',
            self::TemplateActivated => 'Offered the template :name again',
            self::TemplateDeactivated => 'Stopped offering the template :name',
            self::TemplateDeletion => 'Deleted the template :name',
            self::TemplateVersionDrafted => 'Started version :version of the template :name',
            self::TemplateVersionPublished => 'Published version :version of the template :name',
            self::TemplateVersionRetired => 'Retired version :version of the template :name',
            self::TemplateVersionDeletion => 'Threw away version :version of the template :name',
            self::SectionCreation => 'Created the section :name',
            self::SectionUpdate => 'Updated the section :name',
            self::SectionActivated => 'Offered the section :name again',
            self::SectionDeactivated => 'Stopped offering the section :name',
            self::SectionDeletion => 'Deleted the section :name',
            self::SectionVersionDrafted => 'Started version :version of the section :name',
            self::SectionVersionPublished => 'Published version :version of the section :name',
            self::SectionVersionRetired => 'Retired version :version of the section :name',
            self::SectionVersionDeletion => 'Threw away version :version of the section :name',
            self::TemplatePageCreation => 'Added the page :label to the template :name',
            self::TemplatePageUpdate => 'Renamed a page of the template :name to :label',
            self::TemplatePagePositionUpdate => 'Moved the page :label of the template :name',
            self::TemplatePageDeletion => 'Removed the page :label from the template :name',
            self::SectionAddedToTemplate => 'Put the section :section on the template :name',
            self::SectionRemovedFromTemplate => 'Took the section :section off the template :name',
            self::SectionMovedToPage => 'Moved the section :section to the page :label',
            self::TemplateSectionUpdate => 'Changed how the section :section reads on the template :name',
            self::TemplateSectionPositionUpdate => 'Moved the section :section on the template :name',
            self::FieldCreation => 'Added the field :label to the section :name',
            self::FieldUpdate => 'Updated the field :label in the section :name',
            self::FieldPositionUpdate => 'Moved the field :label in the section :name',
            self::FieldDeletion => 'Removed the field :label from the section :name',
            self::ContactCreation => 'Wrote down the contact :name',
            self::ContactNameUpdate => 'Changed the name of :name',
            self::ContactAvatarUpdate => 'Put a picture on :name',
            self::ContactAvatarDeletion => 'Took the picture off :name',
            self::ContactArchived => 'Archived the contact :name',
            self::ContactUnarchived => 'Brought the contact :name back into the lists',
            self::ContactFavourited => 'Pinned the contact :name to the top of the list',
            self::ContactUnfavourited => 'Unpinned the contact :name',
            self::ContactDeletion => 'Deleted the contact :name',
            self::ContactRestoration => 'Brought the contact :name back',
            self::EntityCreation => 'Wrote down the record :name',
            self::EntityNameUpdate => 'Renamed a record to :name',
            self::EntityArchived => 'Archived the record :name',
            self::EntityUnarchived => 'Brought the record :name back into the lists',
            self::EntityDeletion => 'Deleted the record :name',
            self::EntityRestoration => 'Brought the record :name back',
            self::FieldValueRowCreation => 'Added a repetition of :label on :name',
            self::FieldValueRowPositionUpdate => 'Moved a repetition of :label on :name',
            self::FieldValueRowDeletion => 'Removed a repetition of :label on :name',
            self::FieldValueSet => 'Filled in :label on :name',
            self::FieldValueCleared => 'Cleared :label on :name',
            self::FieldValuesDetached => 'Set aside :count values written in :label',
            self::FieldValuesReattached => 'Brought back :count values written in :label',
            self::RecordSnapshotTaken => 'Saved the state of :name',
            self::RecordSnapshotRestored => 'Put :name back to a saved state',
            self::RecordMovedToTemplateVersion => 'Moved :name onto version :version of its template',
            self::MarketplaceListingCreation => 'Prepared :name for the marketplace',
            self::MarketplaceListingUpdate => 'Updated what the marketplace says about :name',
            self::MarketplaceListingSubmission => 'Sent version :version of :name in for review',
            self::MarketplaceListingWithdrawal => 'Took :name out of the marketplace',
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
