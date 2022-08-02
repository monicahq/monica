<?php

namespace App\Helpers;

use App\Models\Contact;
use App\Models\ContactLog;

class ContactLogHelper
{
    /**
     * Return an sentence explaining what the log contains.
     * A log is stored in a json file and needs some kind of processing to make
     * it understandable by a human.
     *
     * @param  ContactLog  $log
     * @return string
     */
    public static function process(ContactLog $log): string
    {
        switch ($log->action_name) {
            case 'contact_created':
                $sentence = trans('contact_log.contact_created');
                break;
            case 'contact_updated':
                $sentence = trans('contact_log.contact_updated');
                break;
            case 'contact_copied_to_another_vault':
                $sentence = ContactLogHelper::contactCopiedToAnotherVault($log);
                break;
            case 'contact_moved_to_another_vault':
                $sentence = ContactLogHelper::contactMovedToAnotherVault($log);
                break;

            case 'label_assigned':
                $sentence = ContactLogHelper::labelAssigned($log);
                break;
            case 'label_removed':
                $sentence = ContactLogHelper::labelRemoved($log);
                break;

            case 'pronoun_assigned':
                $sentence = ContactLogHelper::pronounAssigned($log);
                break;
            case 'pronoun_removed':
                $sentence = ContactLogHelper::pronounRemoved($log);
                break;

            case 'contact_information_created':
                $sentence = ContactLogHelper::contactInformationCreated($log);
                break;
            case 'contact_information_updated':
                $sentence = ContactLogHelper::contactInformationUpdated($log);
                break;
            case 'contact_information_destroyed':
                $sentence = ContactLogHelper::contactInformationDestroyed($log);
                break;

            case 'relationship_set':
                $sentence = ContactLogHelper::relationshipSet($log);
                break;
            case 'relationship_unset':
                $sentence = ContactLogHelper::relationshipUnset($log);
                break;

            case 'contact_address_created':
                $sentence = ContactLogHelper::contactAddressCreated($log);
                break;
            case 'contact_address_updated':
                $sentence = ContactLogHelper::contactAddressUpdated($log);
                break;
            case 'contact_address_destroyed':
                $sentence = ContactLogHelper::contactAddressDestroyed($log);
                break;

            case 'note_created':
                $sentence = ContactLogHelper::noteCreated($log);
                break;
            case 'note_updated':
                $sentence = ContactLogHelper::noteUpdated($log);
                break;
            case 'note_destroyed':
                $sentence = ContactLogHelper::noteDestroyed($log);
                break;

            case 'contact_date_created':
                $sentence = ContactLogHelper::contactDateCreated($log);
                break;
            case 'contact_date_updated':
                $sentence = ContactLogHelper::contactDateUpdated($log);
                break;
            case 'contact_date_destroyed':
                $sentence = ContactLogHelper::contactDateDestroyed($log);
                break;

            case 'contact_template_updated':
                $sentence = ContactLogHelper::templateUpdated($log);
                break;

            case 'loan_created':
                $sentence = ContactLogHelper::loanCreated($log);
                break;
            case 'loan_updated':
                $sentence = ContactLogHelper::loanUpdated($log);
                break;
            case 'loan_destroyed':
                $sentence = ContactLogHelper::loanDestroyed($log);
                break;

            default:
                $sentence = 'No translation';
                break;
        }

        return $sentence;
    }

    private static function contactCopiedToAnotherVault(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_copied_to_another_vault', [
            'name' => $log->object->{'name'},
        ]);

        return $sentence;
    }

    private static function contactMovedToAnotherVault(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_moved_to_another_vault', [
            'initial_vault_name' => $log->object->{'initial_vault_name'},
            'destination_vault_name' => $log->object->{'destination_vault_name'},
        ]);

        return $sentence;
    }

    private static function relationshipSet(ContactLog $log): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('contact_log.relationship_set', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => $contact->name,
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        } else {
            $sentence = trans('contact_log.relationship_set_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        }

        return $sentence;
    }

    private static function relationshipUnset(ContactLog $log): string
    {
        $contact = Contact::find($log->object->{'contact_id'});

        if ($contact) {
            $sentence = trans('contact_log.relationship_unset', [
                'contact_url' => route('contact.show', [
                    'vault' => $contact->vault_id,
                    'contact' => $contact->id,
                ]),
                'contact_name' => $contact->name,
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        } else {
            $sentence = trans('contact_log.relationship_unset_object_deleted', [
                'contact_name' => $log->object->{'contact_name'},
                'relationship_name' => $log->object->{'relationship_name'},
            ]);
        }

        return $sentence;
    }

    private static function labelAssigned(ContactLog $log): string
    {
        $sentence = trans('contact_log.label_assigned', [
            'label_name' => $log->object->{'label_name'},
        ]);

        return $sentence;
    }

    private static function labelRemoved(ContactLog $log): string
    {
        $sentence = trans('contact_log.label_removed', [
            'label_name' => $log->object->{'label_name'},
        ]);

        return $sentence;
    }

    private static function pronounAssigned(ContactLog $log): string
    {
        $sentence = trans('contact_log.pronoun_assigned', [
            'pronoun_name' => $log->object->{'pronoun_name'},
        ]);

        return $sentence;
    }

    private static function pronounRemoved(ContactLog $log): string
    {
        $sentence = trans('contact_log.pronoun_removed', [
            'pronoun_name' => $log->object->{'pronoun_name'},
        ]);

        return $sentence;
    }

    private static function contactInformationCreated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_information_created', [
            'contact_information_type_name' => $log->object->{'contact_information_type_name'},
        ]);

        return $sentence;
    }

    private static function contactInformationUpdated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_information_updated', [
            'contact_information_type_name' => $log->object->{'contact_information_type_name'},
        ]);

        return $sentence;
    }

    private static function contactInformationDestroyed(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_information_destroyed', [
            'contact_information_type_name' => $log->object->{'contact_information_type_name'},
        ]);

        return $sentence;
    }

    private static function contactAddressCreated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_address_created', [
            'address_type_name' => $log->object->{'address_type_name'},
        ]);

        return $sentence;
    }

    private static function contactAddressUpdated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_address_updated', [
            'address_type_name' => $log->object->{'address_type_name'},
        ]);

        return $sentence;
    }

    private static function contactAddressDestroyed(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_address_destroyed', [
            'address_type_name' => $log->object->{'address_type_name'},
        ]);

        return $sentence;
    }

    private static function noteCreated(ContactLog $log): string
    {
        $sentence = trans('contact_log.note_created');

        return $sentence;
    }

    private static function noteUpdated(ContactLog $log): string
    {
        $sentence = trans('contact_log.note_updated');

        return $sentence;
    }

    private static function noteDestroyed(ContactLog $log): string
    {
        $sentence = trans('contact_log.note_destroyed');

        return $sentence;
    }

    private static function contactDateCreated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_date_created');

        return $sentence;
    }

    private static function contactDateUpdated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_date_updated');

        return $sentence;
    }

    private static function contactDateDestroyed(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_date_destroyed');

        return $sentence;
    }

    private static function templateUpdated(ContactLog $log): string
    {
        $sentence = trans('contact_log.contact_template_updated');

        return $sentence;
    }

    private static function loanCreated(ContactLog $log): string
    {
        $sentence = trans('contact_log.loan_created', [
            'loan_name' => $log->object->{'loan_name'},
        ]);

        return $sentence;
    }

    private static function loanUpdated(ContactLog $log): string
    {
        $sentence = trans('contact_log.loan_updated', [
            'loan_name' => $log->object->{'loan_name'},
        ]);

        return $sentence;
    }

    private static function loanDestroyed(ContactLog $log): string
    {
        $sentence = trans('contact_log.loan_destroyed', [
            'loan_name' => $log->object->{'loan_name'},
        ]);

        return $sentence;
    }
}
