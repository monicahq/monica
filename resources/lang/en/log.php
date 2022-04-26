<?php

return [
    'account_created' => 'Created the account',

    // vault actions
    'vault_created' => 'Created the vault <a href=":url">:name</a>',
    'vault_created_object_deleted' => 'Created the vault :name (deleted)',
    'vault_updated' => 'Updated the vault <a href=":url">:name</a>',
    'vault_updated_object_deleted' => 'Updated the vault :name (deleted)',
    'vault_destroyed' => 'Deleted the vault :name',
    'vault_access_grant' => 'Grant the :permission_type permission to :user_name to the vault :vault_name',
    'vault_access_permission_changed' => 'Changed the permission of :user_name to :permission_type in the vault :vault_name',

    // gender actions
    'gender_created' => 'Created the gender :name',
    'gender_updated' => 'Updated the gender :name',
    'gender_destroyed' => 'Deleted the gender :name',

    // label actions
    'label_created' => 'Created the label :label_name',
    'label_updated' => 'Updated the label :label_name',
    'label_destroyed' => 'Deleted the label :label_name',

    // pronoun actions
    'pronoun_created' => 'Created the pronoun :name',
    'pronoun_updated' => 'Updated the pronoun :name',
    'pronoun_destroyed' => 'Deleted the pronoun :name',

    // contact information type actions
    'contact_information_type_created' => 'Created the contact information type :name',
    'contact_information_type_updated' => 'Updated the contact information type :name',
    'contact_information_type_destroyed' => 'Deleted the contact information type :name',

    // address type actions
    'address_type_created' => 'Created the address type :name',
    'address_type_updated' => 'Updated the address type :name',
    'address_type_destroyed' => 'Deleted the address type :name',

    // relationship group types
    'relationship_group_type_created' => 'Created the relationship group type :name',
    'relationship_group_type_updated' => 'Updated the relationship group type :name',
    'relationship_group_type_destroyed' => 'Deleted the relationship group type :name',
    'relationship_type_created' => 'Created the relationship type :name in the group type :group_type_name',
    'relationship_type_updated' => 'Updated the relationship type :name in the group type :group_type_name',
    'relationship_type_destroyed' => 'Deleted the relationship type :name in the group type :group_type_name',

    // roles
    'administrator_privilege_given' => 'Gave administrator privilege to :name',
    'administrator_privilege_removed' => 'Removed administrator privilege of :name',

    // contacts
    'contact_copied_to_another_vault' => 'Copied the contact <a href=":url">:contact_name</a> from the vault :original_vault_name to the vault :target_vault_name',
    'contact_copied_to_another_vault_object_deleted' => 'Copied the contact :contact_name (deleted) from the vault :original_vault_name to the vault :target_vault_name',
    'contact_moved_to_another_vault' => 'Moved the contact <a href=":url">:contact_name</a> from the vault :original_vault_name to the vault :target_vault_name',
    'contact_moved_to_another_vault_object_deleted' => 'Moved the contact :contact_name (deleted) from the vault :original_vault_name to the vault :target_vault_name',
    'contact_created' => 'Created the contact <a href=":url">:name</a>',
    'contact_created_object_deleted' => 'Created the contact :name (deleted)',
    'contact_updated' => 'Updated the contact <a href=":url">:name</a>',
    'contact_updated_object_deleted' => 'Updated the contact :name (deleted)',
    'contact_destroyed' => 'Deleted the contact :name',
    'pronoun_set' => 'Set the pronoun :pronoun_name to the contact <a href=":contact_url">:contact_name</a>',
    'pronoun_set_object_deleted' => 'Set the pronoun :pronoun_name to the contact :contact_name (deleted)',
    'pronoun_unset' => 'Unset the pronoun of the contact <a href=":contact_url">:contact_name</a>',
    'pronoun_unset_object_deleted' => 'Unset the pronoun of the contact :contact_name (deleted)',
    'label_assigned' => 'Assigned the label :label_name to the contact <a href=":contact_url">:contact_name</a>',
    'label_assigned_object_deleted' => 'Assigned the label :label_name to the contact :contact_name (deleted)',
    'label_removed' => 'Removed the label :label_name from the contact <a href=":contact_url">:contact_name</a>',
    'label_removed_object_deleted' => 'Removed the label :label_name from the contact :contact_name (deleted)',
    'contact_information_created' => 'Created the contact information :contact_information_type_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_information_created_object_deleted' => 'Created the contact information :contact_information_type_name for the contact :contact_name (deleted)',
    'contact_information_updated' => 'Updated the contact information :contact_information_type_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_information_updated_object_deleted' => 'Updated the contact information :contact_information_type_name for the contact :contact_name (deleted)',
    'contact_information_destroyed' => 'Deleted the contact information :contact_information_type_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_information_destroyed_object_deleted' => 'Deleted the contact information :contact_information_type_name for the contact :contact_name (deleted)',
    'contact_address_created' => 'Created the contact address :address_type_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_address_created_object_deleted' => 'Created the contact address :address_type_name for the contact :contact_name (deleted)',
    'contact_address_updated' => 'Updated the contact address :address_type_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_address_updated_object_deleted' => 'Updated the contact address :address_type_name for the contact :contact_name (deleted)',
    'contact_address_destroyed' => 'Deleted a contact address for the contact <a href=":contact_url">:contact_name</a>',
    'contact_address_destroyed_object_deleted' => 'Deleted a contact address for the contact :contact_name (deleted)',
    'note_created' => 'Wrote a note for the contact <a href=":contact_url">:contact_name</a>',
    'note_created_object_deleted' => 'Wrote a note for the contact :contact_name (deleted)',
    'note_updated' => 'Updated the note for the contact <a href=":contact_url">:contact_name</a>',
    'note_updated_object_deleted' => 'Updated the note for the contact :contact_name (deleted)',
    'note_destroyed' => 'Deleted the note for the contact <a href=":contact_url">:contact_name</a>',
    'note_destroyed_object_deleted' => 'Deleted the note for the contact :contact_name (deleted)',
    'contact_date_created' => 'Added a date named :label for the contact <a href=":contact_url">:contact_name</a>',
    'contact_date_created_object_deleted' => 'Added a date named :label for the contact :contact_name (deleted)',
    'contact_date_updated' => 'Updated a date named :label for the contact <a href=":contact_url">:contact_name</a>',
    'contact_date_updated_object_deleted' => 'Updated a date named :label for the contact :contact_name (deleted)',
    'contact_date_destroyed' => 'Deleted a date for the contact <a href=":contact_url">:contact_name</a>',
    'contact_date_destroyed_object_deleted' => 'Deleted a date for the contact :contact_name (deleted)',
    'contact_template_updated' => 'Updated the template used to display the contact <a href=":contact_url">:contact_name</a>',
    'contact_template_updated_object_deleted' => 'Updated the template used to display the contact :contact_name (deleted)',
    'contact_reminder_created' => 'Created the reminder called :reminder_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_reminder_created_object_deleted' => 'Created the reminder called :reminder_name for the contact :contact_name (deleted)',
    'contact_reminder_updated' => 'Updated the reminder called :reminder_name for the contact <a href=":contact_url">:contact_name</a>',
    'contact_reminder_updated_object_deleted' => 'Updated the reminder called :reminder_name for the contact :contact_name (deleted)',
    'contact_reminder_destroyed' => 'Deleted a reminder for the contact <a href=":contact_url">:contact_name</a>',
    'contact_reminder_destroyed_object_deleted' => 'Deleted a reminder for the contact :contact_name (deleted)',
    'loan_created' => 'Added a loan called :loan_name for the contact <a href=":contact_url">:contact_name</a>',
    'loan_created_object_deleted' => 'Added a loan called :loan_name for the contact :contact_name (deleted)',
    'loan_updated' => 'Updated the loan called :loan_name for the contact <a href=":contact_url">:contact_name</a>',
    'loan_updated_object_deleted' => 'Updated the loan called :loan_name for the contact :contact_name (deleted)',
    'loan_destroyed' => 'Deleted the loan called :loan_name for the contact <a href=":contact_url">:contact_name</a>',
    'loan_destroyed_object_deleted' => 'Deleted the loan called :loan_name for the contact :contact_name (deleted)',

    // relationships
    'relationship_set' => 'Set the contact <a href=":contact_url">:contact_name</a> as :relationship_name of <a href=":other_contact_url">:other_contact_name</a>',
    'relationship_set_first_contact_deleted' => 'Set the contact :contact_name (deleted) as :relationship_name of <a href=":other_contact_url">:other_contact_name</a>',
    'relationship_set_second_contact_deleted' => 'Set the contact <a href=":contact_url">:contact_name</a> as :relationship_name of :other_contact_name (deleted)',
    'relationship_set_object_deleted' => 'Set the contact :contact_name (deleted) as :relationship_name of :other_contact_name (deleted)',
    'relationship_unset' => 'Unset the contact <a href=":contact_url">:contact_name</a> as related to <a href=":other_contact_url">:other_contact_name</a>',
    'relationship_unset_first_contact_deleted' => 'Unset the contact :contact_name (deleted) as related to <a href=":other_contact_url">:other_contact_name</a>',
    'relationship_unset_second_contact_deleted' => 'Unset the contact <a href=":contact_url">:contact_name</a> as related to :other_contact_name (deleted)',
    'relationship_unset_object_deleted' => 'Unset the contact :contact_name (deleted) as related to :other_contact_name (deleted)',

    // users
    'user_invited' => 'Invited :user_email to the account',
    'user_notification_channel_created' => 'Added a new notification channel called :label of the type :type',
    'user_notification_channel_toggled' => 'Toggled the notification channel called :label of the type :type',
    'user_notification_channel_verified' => 'Verified the notification channel called :label of the type :type',
    'user_notification_channel_destroyed' => 'Deleted the notification channel called :label of the type :type',
];
