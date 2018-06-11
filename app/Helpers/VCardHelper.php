<?php

namespace App\Helpers;

use App\Models\Contact\Contact;
use JeroenDesloovere\VCard\VCard;

class VCardHelper
{
    /**
     * Export a contact as vCard.
     *
     * @param string date
     * @param string timezone
     * @return VCard
     */
    public static function prepareVCard(Contact $contact)
    {
        // define vcard
        $vCard = new VCard();

        // define variables
        $lastname = $contact->last_name;
        $firstname = $contact->first_name;
        $additional = '';
        $prefix = '';
        $suffix = '';

        $vCard->addName($lastname, $firstname, $additional, $prefix, $suffix);
        $vCard->addURL($contact->url);
        $vCard->addCompany($contact->company);
        $vCard->addJobtitle($contact->job);
        $vCard = self::addContactFieldEntriesInVCard($contact, $vCard, 'email');
        $vCard = self::addContactFieldEntriesInVCard($contact, $vCard, 'phone');
        $vCard = self::addAddressToVCard($contact, $vCard);

        return $vCard;
    }

    /**
     * Get the first contact field of the contact for the given field, if it's
     * defined.
     * A contact can have multiple fields of the same type (email, phone, fax...)
     * so we need to take the first one we find.
     *
     * @param  Contact $contact
     * @param  string $fieldType
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public static function getAllEntriesOfASpecificContactFieldType(Contact $contact, string $fieldType)
    {
        $contactFieldType = $contact->account->contactFieldTypes()
                                    ->where('type', $fieldType)
                                    ->first();

        if (! $contactFieldType) {
            return;
        }

        $contactFields = $contact->contactFields()
                                ->where('contact_field_type_id', $contactFieldType->id)
                                ->get();

        if (count($contactFields) == 0) {
            return;
        }

        return $contactFields;
    }

    /**
     * Add a specific contact field in the given vCard file.
     *
     * @param Contact $contact
     * @param VCard   $vCard
     * @param string  $fieldType
     * @return VCard
     */
    public static function addContactFieldEntriesInVCard(Contact $contact, VCard $vCard, String $fieldType)
    {
        $contactFields = self::getAllEntriesOfASpecificContactFieldType($contact, $fieldType);

        if (! $contactFields) {
            return $vCard;
        }

        foreach ($contactFields as $contactField) {
            if ($fieldType == 'email') {
                $vCard->addEmail($contactField->data);
            }

            if ($fieldType == 'phone') {
                $vCard->addPhoneNumber($contactField->data);
            }
        }

        return $vCard;
    }

    /**
     * Add all addresses to the given vCard file.
     *
     * @param Contact $contact
     * @param VCard   $vCard
     */
    public static function addAddressToVCard(Contact $contact, VCard $vCard)
    {
        foreach ($contact->addresses as $address) {
            $vCard->addAddress($address->name,
                                null,
                                $address->street,
                                $address->city,
                                $address->province,
                                $address->postal_code,
                                $address->getCountryName());
        }

        return $vCard;
    }
}
