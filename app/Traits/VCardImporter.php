<?php

namespace App\Traits;

use Sabre\VObject\Reader;
use App\Models\Contact\Gender;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Helpers\CountriesHelper;
use Sabre\VObject\Component\VCard;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;

trait VCardImporter
{
    protected $skippedContacts = 0;
    protected $importedContacts = 0;
    private $contactFieldEmailId;
    private $contactFieldPhoneId;

    public function work($account_id, $subject)
    {
        $matchCount = preg_match_all('/(BEGIN:VCARD.*?END:VCARD)/s', $subject, $matches);

        if (! $this->workInit($matchCount)) {
            return;
        }

        // create special gender for this import
        // we don't know which gender all the contacts are, so we need to create a special status for them, as we
        // can't guess whether they are men, women or else.
        $gender = Gender::where('name', 'vCard')->first();
        if (! $gender) {
            $gender = new Gender;
            $gender->account_id = $account_id;
            $gender->name = 'vCard';
            $gender->save();
        }

        collect($matches[0])->map(function ($vcard) {
            return Reader::read($vcard);
        })->each(function (VCard $vcard) use ($account_id, $gender) {
            if ($this->contactExists($vcard, $account_id)) {
                $this->workContactExists($vcard);
                $this->skippedContacts++;

                return;
            }

            // Skip contact if there isn't a first name or a nickname
            if (! $this->contactHasName($vcard)) {
                $this->workContactNoFirstname($vcard);
                $this->skippedContacts++;

                return;
            }

            $this->vCardToContact($vcard, $account_id, $gender->id);

            $this->importedContacts++;

            $this->workNext($vcard);
        });

        $this->workEnd($matchCount, $this->skippedContacts, $this->importedContacts);
    }

    private function vCardToContact($vcard, $account_id, $gender_id)
    {
        $contact = new Contact();
        $contact->account_id = $account_id;
        $contact->gender_id = $gender_id;

        if ($vcard->N && ! empty($vcard->N->getParts()[1])) {
            $contact->first_name = $this->formatValue($vcard->N->getParts()[1]);
            $contact->middle_name = $this->formatValue($vcard->N->getParts()[2]);
            $contact->last_name = $this->formatValue($vcard->N->getParts()[0]);
        } else {
            $contact->first_name = $this->formatValue($vcard->NICKNAME);
        }

        $contact->company = $this->formatValue($vcard->ORG);
        if ($vcard->ROLE) {
            $contact->job = $this->formatValue($vcard->ROLE);
        }
        if ($vcard->TITLE) {
            $contact->job = $this->formatValue($vcard->TITLE);
        }

        $contact->setAvatarColor();

        $contact->save();

        if ($vcard->BDAY && ! empty((string) $vcard->BDAY)) {
            $birthdate = new \DateTime((string) $vcard->BDAY);

            $specialDate = $contact->setSpecialDate('birthdate', $birthdate->format('Y'), $birthdate->format('m'), $birthdate->format('d'));
            $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
        }

        if ($vcard->ADR) {
            $address = new Address();
            $address->street = $this->formatValue($vcard->ADR->getParts()[2]);
            $address->city = $this->formatValue($vcard->ADR->getParts()[3]);
            $address->province = $this->formatValue($vcard->ADR->getParts()[4]);
            $address->postal_code = $this->formatValue($vcard->ADR->getParts()[5]);
            $address->country = CountriesHelper::find($vcard->ADR->getParts()[6]);
            $address->contact_id = $contact->id;
            $address->account_id = $contact->account_id;
            $address->save();
        }

        if (! is_null($this->formatValue($vcard->EMAIL))) {
            // Saves the email

            $isValidEmail = filter_var($this->formatValue($vcard->EMAIL), FILTER_VALIDATE_EMAIL);

            if ($isValidEmail) {
                $contactField = new ContactField;
                $contactField->contact_id = $contact->id;
                $contactField->account_id = $contact->account_id;
                $contactField->data = $this->formatValue($vcard->EMAIL);
                $contactField->contact_field_type_id = $this->contactFieldEmailId();
                $contactField->save();
            }
        }

        if (! is_null($this->formatValue($vcard->TEL))) {
            // Saves the phone number
            $contactField = new ContactField;
            $contactField->contact_id = $contact->id;
            $contactField->account_id = $contact->account_id;
            $contactField->data = $this->formatValue($vcard->TEL);
            $contactField->contact_field_type_id = $this->contactFieldPhoneId();
            $contactField->save();
        }

        $contact->updateGravatar();
    }

    private function contactFieldEmailId()
    {
        if (! $this->contactFieldEmailId) {
            $contactFieldType = ContactFieldType::where('type', 'email')->first();
            $this->contactFieldEmailId = $contactFieldType->id;
        }

        return $this->contactFieldEmailId;
    }

    private function contactFieldPhoneId()
    {
        if (! $this->contactFieldPhoneId) {
            $contactFieldType = ContactFieldType::where('type', 'phone')->first();
            $this->contactFieldPhoneId = $contactFieldType->id;
        }

        return $this->contactFieldPhoneId;
    }

    protected function workInit($matchCount)
    {
        return true;
    }

    protected function workContactExists($vcard)
    {
    }

    protected function workContactNoFirstname($vcard)
    {
    }

    protected function workNext($vcard)
    {
    }

    protected function workEnd($matchCount, $skippedContacts, $importedContacts)
    {
    }

    /**
     * Formats and returns a string for the contact.
     *
     * @param null|string $value
     * @return null|string
     */
    private function formatValue($value)
    {
        return ! empty((string) $value) ? (string) $value : null;
    }

    /**
     * Checks whether a contact already exists for a given account.
     *
     * @param VCard $vcard
     * @param int $account_id
     * @return bool
     */
    private function contactExists(VCard $vcard, int $account_id)
    {
        $email = (string) $vcard->EMAIL;

        $isValidEmail = filter_var($email, FILTER_VALIDATE_EMAIL);

        if (! $isValidEmail) {
            return false;
        }

        $contactFieldType = ContactFieldType::where([
            ['account_id', $account_id],
            ['type', 'email'],
        ])->first();

        $contactField = null;

        if ($contactFieldType) {
            $contactField = ContactField::where([
                ['account_id', $account_id],
                ['data', $email],
                ['contact_field_type_id', $contactFieldType->id],
            ])->first();
        }

        return $email && $contactField;
    }

    /**
     * Checks whether a contact has a first name or a nickname.
     * Nickname is used as a fallback if no first name is provided.
     *
     * @param VCard $vcard
     * @return bool
     */
    public function contactHasName(VCard $vcard): bool
    {
        return ! empty($vcard->N->getParts()[1]) || ! empty((string) $vcard->NICKNAME);
    }
}
