<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Vault;
use App\Models\Contact;
use App\Models\AuditLog;

class AuditLogHelper
{
    /**
     * Return an sentence explaining what the log contains.
     * A log is stored in a json file and needs some kind of processing to make
     * it understandable by a human.
     *
     * @param  AuditLog  $log
     * @param  User  $user
     * @return string
     */
    public static function process(AuditLog $log, User $user): string
    {
        switch ($log->action_name) {
            case 'account_created':
                $sentence = trans('log.account_created');
                break;

            case 'vault_created':
                $sentence = AuditLogHelper::vaultCreated($log, $user);
                break;

            case 'vault_updated':
                $sentence = AuditLogHelper::vaultUpdated($log, $user);
                break;

            case 'vault_destroyed':
                $sentence = AuditLogHelper::vaultDestroyed($log, $user);
                break;

            case 'vault_access_permission_changed':
                $sentence = AuditLogHelper::vaultAccessPermissionChanged($log, $user);
                break;

            case 'vault_access_grant':
                $sentence = AuditLogHelper::vaultAccessGrant($log, $user);
                break;

            case 'gender_created':
                $sentence = AuditLogHelper::genderCreated($log, $user);
                break;

            case 'gender_updated':
                $sentence = AuditLogHelper::genderUpdated($log, $user);
                break;

            case 'gender_destroyed':
                $sentence = AuditLogHelper::genderDestroyed($log, $user);
                break;

            case 'label_created':
                $sentence = AuditLogHelper::labelCreated($log, $user);
                break;

            case 'label_updated':
                $sentence = AuditLogHelper::labelUpdated($log, $user);
                break;

            case 'label_destroyed':
                $sentence = AuditLogHelper::labelDestroyed($log, $user);
                break;

            case 'contact_information_type_created':
                $sentence = AuditLogHelper::contactInformationTypeCreated($log, $user);
                break;

            case 'contact_information_type_updated':
                $sentence = AuditLogHelper::contactInformationTypeUpdated($log, $user);
                break;

            case 'contact_information_type_destroyed':
                $sentence = AuditLogHelper::contactInformationTypeDestroyed($log, $user);
                break;

            case 'address_type_created':
                $sentence = AuditLogHelper::contactAddressTypeCreated($log, $user);
                break;

            case 'address_type_updated':
                $sentence = AuditLogHelper::contactAddressTypeUpdated($log, $user);
                break;

            case 'address_type_destroyed':
                $sentence = AuditLogHelper::contactAddressTypeDestroyed($log, $user);
                break;

            case 'pronoun_created':
                $sentence = AuditLogHelper::pronounCreated($log, $user);
                break;

            case 'pronoun_updated':
                $sentence = AuditLogHelper::pronounUpdated($log, $user);
                break;

            case 'pronoun_destroyed':
                $sentence = AuditLogHelper::pronounDestroyed($log, $user);
                break;

            case 'relationship_group_type_created':
                $sentence = AuditLogHelper::relationshipGroupTypeCreated($log, $user);
                break;

            case 'relationship_group_type_updated':
                $sentence = AuditLogHelper::relationshipGroupTypeUpdated($log, $user);
                break;

            case 'relationship_group_type_destroyed':
                $sentence = AuditLogHelper::relationshipGroupTypeDestroyed($log, $user);
                break;

            case 'relationship_type_created':
                $sentence = AuditLogHelper::relationshipTypeCreated($log, $user);
                break;

            case 'relationship_type_updated':
                $sentence = AuditLogHelper::relationshipTypeUpdated($log, $user);
                break;

            case 'relationship_type_destroyed':
                $sentence = AuditLogHelper::relationshipTypeDestroyed($log, $user);
                break;

            case 'administrator_privilege_given':
                $sentence = AuditLogHelper::administratorPrivilegeGiven($log, $user);
                break;

            case 'administrator_privilege_removed':
                $sentence = AuditLogHelper::administratorPrivilegeRemoved($log, $user);
                break;

            case 'contact_created':
                $sentence = AuditLogHelper::contactCreated($log, $user);
                break;

            case 'contact_updated':
                $sentence = AuditLogHelper::contactUpdated($log, $user);
                break;

            case 'contact_destroyed':
                $sentence = AuditLogHelper::contactDestroyed($log, $user);
                break;

            case 'contact_copied_to_another_vault':
                $sentence = AuditLogHelper::contactCopiedToAnotherVault($log, $user);
                break;

            case 'contact_moved_to_another_vault':
                $sentence = AuditLogHelper::contactMovedToAnotherVault($log, $user);
                break;

            case 'relationship_set':
                $sentence = AuditLogHelper::relationshipSet($log, $user);
                break;

            case 'relationship_unset':
                $sentence = AuditLogHelper::relationshipUnset($log, $user);
                break;

            case 'pronoun_set':
                $sentence = AuditLogHelper::pronounSet($log, $user);
                break;

            case 'pronoun_unset':
                $sentence = AuditLogHelper::pronounUnset($log, $user);
                break;

            case 'label_assigned':
                $sentence = AuditLogHelper::labelAssigned($log, $user);
                break;

            case 'label_removed':
                $sentence = AuditLogHelper::labelRemoved($log, $user);
                break;

            case 'contact_information_created':
                $sentence = AuditLogHelper::contactInformationCreated($log, $user);
                break;

            case 'contact_information_updated':
                $sentence = AuditLogHelper::contactInformationUpdated($log, $user);
                break;

            case 'contact_information_destroyed':
                $sentence = AuditLogHelper::contactInformationDestroyed($log, $user);
                break;

            case 'contact_address_created':
                $sentence = AuditLogHelper::contactAddressCreated($log, $user);
                break;

            case 'contact_address_updated':
                $sentence = AuditLogHelper::contactAddressUpdated($log, $user);
                break;

            case 'contact_address_destroyed':
                $sentence = AuditLogHelper::contactAddressDestroyed($log, $user);
                break;

            case 'note_created':
                $sentence = AuditLogHelper::noteCreated($log, $user);
                break;

            case 'note_updated':
                $sentence = AuditLogHelper::noteUpdated($log, $user);
                break;

            case 'note_destroyed':
                $sentence = AuditLogHelper::noteDestroyed($log, $user);
                break;

            case 'user_invited':
                $sentence = AuditLogHelper::userInvited($log, $user);
                break;

            case 'contact_template_updated':
                $sentence = AuditLogHelper::contactTemplateUpdated($log, $user);
                break;

            case 'contact_date_created':
                $sentence = AuditLogHelper::contactDateCreated($log, $user);
                break;

            case 'contact_date_updated':
                $sentence = AuditLogHelper::contactDateUpdated($log, $user);
                break;

            case 'contact_date_destroyed':
                $sentence = AuditLogHelper::contactDateDestroyed($log, $user);
                break;

            case 'contact_reminder_created':
                $sentence = AuditLogHelper::contactReminderCreated($log, $user);
                break;

            case 'contact_reminder_updated':
                $sentence = AuditLogHelper::contactReminderUpdated($log, $user);
                break;

            case 'contact_reminder_destroyed':
                $sentence = AuditLogHelper::contactReminderDestroyed($log, $user);
                break;

            case 'user_notification_channel_created':
                $sentence = AuditLogHelper::userNotificationChannelCreated($log, $user);
                break;

            case 'user_notification_channel_toggled':
                $sentence = AuditLogHelper::userNotificationChannelToggled($log, $user);
                break;

            case 'user_notification_channel_verified':
                $sentence = AuditLogHelper::userNotificationChannelVerified($log, $user);
                break;

            case 'user_notification_channel_destroyed':
                $sentence = AuditLogHelper::userNotificationChannelDestroyed($log, $user);
                break;

            default:
                $sentence = 'No translation';
                break;
        }

        return $sentence;
    }

    private static function vaultCreated(AuditLog $log, User $user): string
    {
        $vault = Vault::find($log->object->{'vault_id'});

        if ($vault) {
            $sentence = trans('log.vault_created', [
                'url' => route('vault.show', [
                    'vault' => $vault->id,
                ]),
                'name' => $vault->name,
            ]);
        } else {
            $sentence = trans('log.vault_created_object_deleted', [
                'name' => $log->object->{'vault_name'},
            ]);
        }

        return $sentence;
    }

    private static function vaultUpdated(AuditLog $log, User $user): string
    {
        $vault = Vault::find($log->object->{'vault_id'});

        if ($vault) {
            $sentence = trans('log.vault_updated', [
                'url' => route('vault.show', [
                    'vault' => $vault->id,
                ]),
                'name' => $vault->name,
            ]);
        } else {
            $sentence = trans('log.vault_updated_object_deleted', [
                'name' => $log->object->{'vault_name'},
            ]);
        }

        return $sentence;
    }

    private static function vaultDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.vault_destroyed', [
            'name' => $log->object->{'vault_name'},
        ]);
    }

    private static function vaultAccessGrant(AuditLog $log, User $user): string
    {
        return trans('log.vault_access_grant', [
            'user_name' => $log->object->{'user_name'},
            'vault_name' => $log->object->{'vault_name'},
            'permission_type' => $log->object->{'permission_type'},
        ]);
    }

    private static function vaultAccessPermissionChanged(AuditLog $log, User $user): string
    {
        return trans('log.vault_access_permission_changed', [
            'user_name' => $log->object->{'user_name'},
            'vault_name' => $log->object->{'vault_name'},
            'permission_type' => $log->object->{'permission_type'},
        ]);
    }

    private static function genderCreated(AuditLog $log, User $user): string
    {
        return trans('log.gender_created', [
            'name' => $log->object->{'gender_name'},
        ]);
    }

    private static function genderUpdated(AuditLog $log, User $user): string
    {
        return trans('log.gender_updated', [
            'name' => $log->object->{'gender_name'},
        ]);
    }

    private static function genderDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.gender_destroyed', [
            'name' => $log->object->{'gender_name'},
        ]);
    }

    private static function labelCreated(AuditLog $log, User $user): string
    {
        return trans('log.label_created', [
            'label_name' => $log->object->{'label_name'},
        ]);
    }

    private static function labelUpdated(AuditLog $log, User $user): string
    {
        return trans('log.label_updated', [
            'label_name' => $log->object->{'label_name'},
        ]);
    }

    private static function labelDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.label_destroyed', [
            'label_name' => $log->object->{'label_name'},
        ]);
    }

    private static function contactInformationTypeCreated(AuditLog $log, User $user): string
    {
        return trans('log.contact_information_type_created', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function contactInformationTypeUpdated(AuditLog $log, User $user): string
    {
        return trans('log.contact_information_type_updated', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function contactInformationTypeDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.contact_information_type_destroyed', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function contactAddressTypeCreated(AuditLog $log, User $user): string
    {
        return trans('log.address_type_created', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function contactAddressTypeUpdated(AuditLog $log, User $user): string
    {
        return trans('log.address_type_updated', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function contactAddressTypeDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.address_type_destroyed', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function pronounCreated(AuditLog $log, User $user): string
    {
        return trans('log.pronoun_created', [
            'name' => $log->object->{'pronoun_name'},
        ]);
    }

    private static function pronounUpdated(AuditLog $log, User $user): string
    {
        return trans('log.pronoun_updated', [
            'name' => $log->object->{'pronoun_name'},
        ]);
    }

    private static function pronounDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.pronoun_destroyed', [
            'name' => $log->object->{'pronoun_name'},
        ]);
    }

    private static function relationshipGroupTypeCreated(AuditLog $log, User $user): string
    {
        return trans('log.relationship_group_type_created', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function relationshipGroupTypeUpdated(AuditLog $log, User $user): string
    {
        return trans('log.relationship_group_type_updated', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function relationshipTypeDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.relationship_type_destroyed', [
            'name' => $log->object->{'name'},
            'group_type_name' => $log->object->{'group_type_name'},
        ]);
    }

    private static function relationshipTypeCreated(AuditLog $log, User $user): string
    {
        return trans('log.relationship_type_created', [
            'name' => $log->object->{'name'},
            'group_type_name' => $log->object->{'group_type_name'},
        ]);
    }

    private static function relationshipTypeUpdated(AuditLog $log, User $user): string
    {
        return trans('log.relationship_type_updated', [
            'name' => $log->object->{'name'},
            'group_type_name' => $log->object->{'group_type_name'},
        ]);
    }

    private static function relationshipGroupTypeDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.relationship_group_type_destroyed', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function administratorPrivilegeGiven(AuditLog $log, User $user): string
    {
        $user = User::find($log->object->{'user_id'});

        if ($user) {
            $sentence = trans('log.administrator_privilege_given', [
                'name' => $user->name,
            ]);
        } else {
            $sentence = trans('log.administrator_privilege_given', [
                'name' => $log->object->{'user_name'},
            ]);
        }

        return $sentence;
    }

    private static function administratorPrivilegeRemoved(AuditLog $log, User $user): string
    {
        $user = User::find($log->object->{'user_id'});

        if ($user) {
            $sentence = trans('log.administrator_privilege_removed', [
                'name' => $user->name,
            ]);
        } else {
            $sentence = trans('log.administrator_privilege_removed', [
                'name' => $log->object->{'user_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactCreated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'id'});

        if ($contact) {
            $sentence = trans('log.contact_created', [
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.contact_created_object_deleted', [
                'name' => $log->object->{'name'},
            ]);
        }

        return $sentence;
    }

    private static function contactUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'id'});

        if ($contact) {
            $sentence = trans('log.contact_updated', [
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.contact_updated_object_deleted', [
                'name' => $log->object->{'name'},
            ]);
        }

        return $sentence;
    }

    private static function contactDestroyed(AuditLog $log, User $user): string
    {
        return trans('log.contact_destroyed', [
            'name' => $log->object->{'name'},
        ]);
    }

    private static function contactCopiedToAnotherVault(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_copied_to_another_vault', [
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'original_vault_name' => $log->object->{'original_vault_name'},
                'target_vault_name' => $log->object->{'target_vault_name'},
            ]);
        } else {
            $sentence = trans('log.contact_copied_to_another_vault_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'original_vault_name' => $log->object->{'original_vault_name'},
                'target_vault_name' => $log->object->{'target_vault_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactMovedToAnotherVault(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_moved_to_another_vault', [
                'url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'original_vault_name' => $log->object->{'original_vault_name'},
                'target_vault_name' => $log->object->{'target_vault_name'},
            ]);
        } else {
            $sentence = trans('log.contact_moved_to_another_vault_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'original_vault_name' => $log->object->{'original_vault_name'},
                'target_vault_name' => $log->object->{'target_vault_name'},
            ]);
        }

        return $sentence;
    }

    private static function relationshipSet(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});
        $otherContact = Contact::find($log->object->{'other_contact_id'});

        if ($contact && $otherContact) {
            $sentence = trans('log.relationship_set', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'other_contact_url' => route('contact.show', [
                    'vault' => $otherContact->vault_id,
                    'contact' => $otherContact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'other_contact_name' => $otherContact->name,
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        } elseif ($contact && ! $otherContact) {
            $sentence = trans('log.relationship_set_second_contact_deleted', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'other_contact_name' => $log->object->{'other_contact_name'},
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        } elseif (! $contact && $otherContact) {
            $sentence = trans('log.relationship_set_first_contact_deleted', [
                'other_contact_url' => route('contact.show', [
                    'vault' => $otherContact->vault_id,
                    'contact' => $otherContact->id,
                ]),
                'contact_name' => $log->object->{'contact_name'},
                'other_contact_name' => $otherContact->name,
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        } else {
            $sentence = trans('log.relationship_set_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'other_contact_name' => $log->object->{'other_contact_name'},
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        }

        return $sentence;
    }

    private static function relationshipUnset(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});
        $otherContact = Contact::find($log->object->{'other_contact_id'});

        if ($contact && $otherContact) {
            $sentence = trans('log.relationship_unset', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'other_contact_url' => route('contact.show', [
                    'vault' => $otherContact->vault_id,
                    'contact' => $otherContact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'other_contact_name' => $otherContact->name,
            ]);
        } elseif ($contact && ! $otherContact) {
            $sentence = trans('log.relationship_unset_second_contact_deleted', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'other_contact_name' => $log->object->{'other_contact_name'},
            ]);
        } elseif (! $contact && $otherContact) {
            $sentence = trans('log.relationship_unset_first_contact_deleted', [
                'other_contact_url' => route('contact.show', [
                    'vault' => $otherContact->vault_id,
                    'contact' => $otherContact->id,
                ]),
                'contact_name' => $log->object->{'contact_name'},
                'other_contact_name' => $otherContact->name,
            ]);
        } else {
            $sentence = trans('log.relationship_unset_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'other_contact_name' => $log->object->{'other_contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function pronounSet(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.pronoun_set', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'pronoun_name' => $log->object->{'pronoun_name'},
            ]);
        } else {
            $sentence = trans('log.pronoun_set_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'pronoun_name' => $log->object->{'pronoun_name'},
            ]);
        }

        return $sentence;
    }

    private static function pronounUnset(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.pronoun_unset', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.pronoun_unset_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function labelAssigned(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.label_assigned', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'label_name' => $log->object->{'label_name'},
            ]);
        } else {
            $sentence = trans('log.label_assigned_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'label_name' => $log->object->{'label_name'},
            ]);
        }

        return $sentence;
    }

    private static function labelRemoved(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.label_removed', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'label_name' => $log->object->{'label_name'},
            ]);
        } else {
            $sentence = trans('log.label_removed_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'label_name' => $log->object->{'label_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactInformationCreated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_information_created', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'contact_information_type_name' => $log->object->{'contact_information_type_name'},
            ]);
        } else {
            $sentence = trans('log.contact_information_created_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'contact_information_type_name' => $log->object->{'contact_information_type_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactInformationUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_information_updated', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'contact_information_type_name' => $log->object->{'contact_information_type_name'},
            ]);
        } else {
            $sentence = trans('log.contact_information_updated_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'contact_information_type_name' => $log->object->{'contact_information_type_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactInformationDestroyed(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_information_destroyed', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'contact_information_type_name' => $log->object->{'contact_information_type_name'},
            ]);
        } else {
            $sentence = trans('log.contact_information_destroyed_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'contact_information_type_name' => $log->object->{'contact_information_type_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactAddressCreated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_address_created', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'address_type_name' => $log->object->{'address_type_name'},
            ]);
        } else {
            $sentence = trans('log.contact_address_created_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'address_type_name' => $log->object->{'address_type_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactAddressUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_address_updated', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'address_type_name' => $log->object->{'address_type_name'},
            ]);
        } else {
            $sentence = trans('log.contact_address_updated_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'address_type_name' => $log->object->{'address_type_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactAddressDestroyed(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_address_destroyed', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'address_type_name' => $log->object->{'address_type_name'},
            ]);
        } else {
            $sentence = trans('log.contact_address_destroyed_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'address_type_name' => $log->object->{'address_type_name'},
            ]);
        }

        return $sentence;
    }

    private static function noteCreated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.note_created', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.note_created_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function noteUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.note_updated', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.note_updated_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function noteDestroyed(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.note_destroyed', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.note_destroyed_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function userInvited(AuditLog $log, User $user): string
    {
        $sentence = trans('log.user_invited', [
            'user_email' => $log->object->{'user_email'},
        ]);

        return $sentence;
    }

    private static function contactTemplateUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_template_updated', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.contact_template_updated_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactDateCreated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_date_created', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'label' => $log->object->{'label'},
            ]);
        } else {
            $sentence = trans('log.contact_date_created_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'label' => $log->object->{'label'},
            ]);
        }

        return $sentence;
    }

    private static function contactDateUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_date_updated', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'label' => $log->object->{'label'},
            ]);
        } else {
            $sentence = trans('log.contact_date_updated_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'label' => $log->object->{'label'},
            ]);
        }

        return $sentence;
    }

    private static function contactDateDestroyed(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_date_destroyed', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.contact_date_destroyed_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactReminderCreated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_reminder_created', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'reminder_name' => $log->object->{'reminder_name'},
            ]);
        } else {
            $sentence = trans('log.contact_reminder_created_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'reminder_name' => $log->object->{'reminder_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactReminderUpdated(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_reminder_updated', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
                'reminder_name' => $log->object->{'reminder_name'},
            ]);
        } else {
            $sentence = trans('log.contact_reminder_updated_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'reminder_name' => $log->object->{'reminder_name'},
            ]);
        }

        return $sentence;
    }

    private static function contactReminderDestroyed(AuditLog $log, User $user): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('log.contact_reminder_destroyed', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => NameHelper::formatContactName($user, $contact),
            ]);
        } else {
            $sentence = trans('log.contact_reminder_destroyed_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
            ]);
        }

        return $sentence;
    }

    private static function userNotificationChannelCreated(AuditLog $log, User $user): string
    {
        $sentence = trans('log.user_notification_channel_created', [
            'label' => $log->object->{'label'},
            'type' => $log->object->{'type'},
        ]);

        return $sentence;
    }

    private static function userNotificationChannelToggled(AuditLog $log, User $user): string
    {
        $sentence = trans('log.user_notification_channel_toggled', [
            'label' => $log->object->{'label'},
            'type' => $log->object->{'type'},
        ]);

        return $sentence;
    }

    private static function userNotificationChannelVerified(AuditLog $log, User $user): string
    {
        $sentence = trans('log.user_notification_channel_verified', [
            'label' => $log->object->{'label'},
            'type' => $log->object->{'type'},
        ]);

        return $sentence;
    }

    private static function userNotificationChannelDestroyed(AuditLog $log, User $user): string
    {
        $sentence = trans('log.user_notification_channel_destroyed', [
            'label' => $log->object->{'label'},
            'type' => $log->object->{'type'},
        ]);

        return $sentence;
    }
}
