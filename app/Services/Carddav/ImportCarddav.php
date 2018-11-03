<?php

namespace App\Services\Carddav;

use App\Helpers\VCardHelper;
use App\Helpers\LocaleHelper;
use App\Services\BaseService;
use App\Models\Contact\Gender;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Helpers\CountriesHelper;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;

class ImportCarddav extends BaseService
{
    public const BEHAVIOUR_ADD = 'behaviour_add';
    public const BEHAVIOUR_REPLACE = 'behaviour_replace';

    public const ERROR_CONTACT_EXIST = -1;
    public const ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME = -2;

    /**
     * Valids value for frequency type.
     *
     * @var array
     */
    public static $behaviourTypes = [
        BEHAVIOUR_ADD, BEHAVIOUR_REPLACE,
    ];

    /**
     * The contact field email object.
     *
     * @var int
     */
    protected $contactFieldEmailId;

    /**
     * The contact field phone object.
     *
     * @var int
     */
    protected $contactFieldPhoneId;

    /**
     * The Account id.
     *
     * @var int
     */
    protected $accountId;

    /**
     * The "Vcard" gender that will be associated with all imported contacts.
     *
     * @var array[Gender]
     */
    protected $genders;

    /**
     * Create a new command.
     *
     * @param int accountId
     */
    public function __construct($accountId)
    {
        $this->accountId = $accountId;
        parent::__construct();
    }

    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'user_id' => 'required|integer',
            'entry' => 'required|string',
            'behaviour' => [
                'required',
                Rule::in(self::$behaviourTypes),
            ],
        ];
    }

    /**
     * Destroy all documents in an account.
     *
     * @param array $data
     * @return int
     */
    public function execute(array $data) : int
    {
        $this->validate(
            $data
            + ['account_id' => $this->accountId]
        );

        $behaviour = $data['behaviour'] ?: self::BEHAVIOUR_ADD;
        $entry = $data['entry'];

        if (! $this->canImportCurrentEntry($entry)) {
            return self::ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME;
        }

        $contact = $this->existingContact($entry);
        if ($contact && $behaviour === self::BEHAVIOUR_ADD) {
            return self::ERROR_CONTACT_EXIST;
        }

        $contact = $this->createContactFromCurrentEntry($contact, $entry->GENDER);

        return (int) $contact != null;
    }

    /**
     * Get or create the gender called "Vcard" that is associated with all
     * imported contacts.
     *
     * @param  char  $genderCode
     * @return Gender
     */
    private function getGender($genderCode)
    {
        if (! $this->genders[$genderCode]) {
            switch ($genderCode) {
                case 'M':
                    $gender = $this->getGenderOfName('Man') ?? $this->getGenderOfName('vCard');
                    break;
                case 'F':
                    $gender = $this->getGenderOfName('Woman') ?? $this->getGenderOfName('vCard');
                    break;
                default:
                    $gender = $this->getGenderOfName('vCard');
                    break;
            }
    
            if (! $gender) {
                $gender = new Gender;
                $gender->account_id = $this->accountId;
                $gender->name = 'vCard';
                $gender->save();
            }

            $this->genders[$genderCode] = $gender;
        }

        return $this->genders[$genderCode];
    }

    /**
     * Get the gender.
     *
     * @param  string  $name
     * @return Gender
     */
    private function getGenderOfName($name)
    {
        return Gender::where([
            ['account_id', $this->accountId],
            ['name', $name]
        ])->first();
    }

    /**
     * Check whether a contact has a first name or a nickname. If not, contact
     * can not be imported.
     *
     * @param VCard $entry
     * @return bool
     */
    private function canImportCurrentEntry($entry): bool
    {
        return
            $this->hasFirstnameInN($entry) ||
            $this->hasNickname($entry) ||
            $this->hasFN($entry);
    }

    /**
     * @param  VCard $entry
     * @return bool
     */
    private function hasFirstnameInN($entry): bool
    {
        return $entry->N !== null && ! empty($entry->N->getParts()[1]);
    }

    /**
     * @param  VCard $entry
     * @return bool
     */
    private function hasNICKNAME($entry): bool
    {
        return ! empty((string) $entry->NICKNAME);
    }

    /**
     * @param  VCard $entry
     * @return bool
     */
    private function hasFN($entry): bool
    {
        return ! empty((string) $entry->FN);
    }

    /**
     * Check whether the email is valid.
     *
     * @param string $email
     * @return bool
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check whether the contact already exists in the database.
     *
     * @return Contact|null
     */
    private function existingContact($entry)
    {
        if (is_null($entry->EMAIL)) {
            return;
        }

        $email = (string) $entry->EMAIL;

        if (! $this->isValidEmail($email)) {
            return;
        }

        $contactField = ContactField::where([
            ['account_id', $this->accountId],
            ['contact_field_type_id', $this->contactFieldEmailId()],
        ])->whereIn('data', iterator_to_array($email))->first();

        if ($contactField) {
            return $contactField->contact;
        }
    }

    /**
     * Return the name and email address of the current entry.
     * John Doe Johnny john@doe.com.
     *
     * @param  VCard $entry
     * @return string
     */
    private function name($entry): string
    {
        if ($this->hasFirstnameInN($entry)) {
            $name = $this->formatValue($entry->N->getParts()[1]);
            $name .= ' '.$this->formatValue($entry->N->getParts()[2]);
            $name .= ' '.$this->formatValue($entry->N->getParts()[0]);
            $name .= ' '.$this->formatValue($entry->EMAIL);
        } elseif ($this->hasNICKNAME($entry)) {
            $name = $this->formatValue($entry->NICKNAME);
            $name .= ' '.$this->formatValue($entry->EMAIL);
        } elseif ($this->hasFN($entry)) {
            $name = $this->formatValue($entry->FN);
            $name .= ' '.$this->formatValue($entry->EMAIL);
        } else {
            $name = trans('settings.import_vcard_unknown_entry');
        }

        return $name;
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
     * Create the Contact object matching the current entry.
     *
     * @param  Contact $contact
     * @param  VCard $entry
     * @return Contact
     */
    private function createContactFromCurrentEntry(Contact $contact, $entry)
    {
        if (! $contact) {
            $contact = new Contact;
            $contact->account_id = $this->accountId;
            $contact->gender_id = $this->getGender($entry->GENDER)->id;
            $contact->setAvatarColor();
            $contact->save();
        }

        $this->importNames($contact, $entry);
        $this->importWorkInformation($contact, $entry);
        $this->importBirthday($contact, $entry);
        $this->importAddress($contact, $entry);
        $this->importEmail($contact, $entry);
        $this->importTel($contact, $entry);

        $contact->save();

        return $contact;
    }

    /**
     * Import names of the contact.
     *
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importNames(Contact $contact, $entry): void
    {
        if ($this->hasFirstnameInN($entry)) {
            $this->importFromN($contact, $entry);
        } elseif ($this->hasNICKNAME($entry)) {
            $this->importFromNICKNAME($contact, $entry);
        } elseif ($this->hasFN($entry)) {
            $this->importFromFN($contact, $entry);
        } else {
            throw new \LogicException('Check if you can import entry!');
        }
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importFromN(Contact $contact, $entry): void
    {
        $contact->first_name = $this->formatValue($entry->N->getParts()[1]);
        $contact->middle_name = $this->formatValue($entry->N->getParts()[2]);
        $contact->last_name = $this->formatValue($entry->N->getParts()[0]);
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importFromNICKNAME(Contact $contact, $entry): void
    {
        $contact->first_name = $this->formatValue($entry->NICKNAME);
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importFromFN(Contact $contact, $entry): void
    {
        $fullnameParts = preg_split('/ +/', $entry->FN);
        $contact->first_name = $this->formatValue($fullnameParts[0]);
        $contact->last_name = $this->formatValue($fullnameParts[1]);
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importWorkInformation(Contact $contact, $entry): void
    {
        if ($entry->ORG) {
            $contact->company = $this->formatValue($entry->ORG);
        }

        if ($entry->ROLE) {
            $contact->job = $this->formatValue($entry->ROLE);
        }

        if ($entry->TITLE) {
            $contact->job = $this->formatValue($entry->TITLE);
        }
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importBirthday(Contact $contact, $entry): void
    {
        if ($entry->BDAY && ! empty((string) $entry->BDAY)) {
            try {
                $birthdate = new \DateTime((string) $entry->BDAY);
            } catch (\Exception $e) {
                return;
            }

            $specialDate = $contact->setSpecialDate('birthdate', $birthdate->format('Y'), $birthdate->format('m'), $birthdate->format('d'));
            $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
        }
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importAddress(Contact $contact, $entry): void
    {
        if (! $entry->ADR) {
            return;
        }

        foreach ($entry->ADR as $adr) {
            Address::firstOrCreate([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'street' => $this->formatValue($adr->getParts()[2]),
                'city' => $this->formatValue($adr->getParts()[3]),
                'province' => $this->formatValue($adr->getParts()[4]),
                'postal_code' => $this->formatValue($adr->getParts()[5]),
                'country' => CountriesHelper::find($adr->getParts()[6]),
            ]);
        }
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importEmail(Contact $contact, $entry): void
    {
        if (is_null($entry->EMAIL)) {
            return;
        }

        foreach ($entry->EMAIL as $email) {
            if ($this->isValidEmail($email)) {
                ContactField::firstOrCreate([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'data' => $this->formatValue($email),
                    'contact_field_type_id' => $this->contactFieldEmailId(),
                ]);
            }
        }
    }

    /**
     * @param Contact $contact
     * @param  VCard $entry
     * @return void
     */
    private function importTel(Contact $contact, $entry): void
    {
        if (is_null($entry->TEL)) {
            return;
        }

        foreach ($entry->TEL as $tel) {
            $tel = (string) $entry->TEL;

            $countryISO = VCardHelper::getCountryISOFromSabreVCard($entry);

            $tel = LocaleHelper::formatTelephoneNumberByISO($tel, $countryISO);

            ContactField::firstOrCreate([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'data' => $this->formatValue($tel),
                'contact_field_type_id' => $this->contactFieldPhoneId(),
            ]);
        }
    }

    /**
     * @return int
     */
    private function contactFieldEmailId(): int
    {
        if (! $this->contactFieldEmailId) {
            $contactFieldType = ContactFieldType::where([
                ['account_id', $this->accountId],
                ['type', 'email'],
            ])->first();
            if ($contactFieldType) {
                $this->contactFieldEmailId = $contactFieldType->id;
            } else {
                // TODO
            }
        }

        return $this->contactFieldEmailId;
    }

    /**
     * @return int
     */
    private function contactFieldPhoneId(): int
    {
        if (! $this->contactFieldPhoneId) {
            $contactFieldType = ContactFieldType::where([
                ['account_id', $this->accountId],
                ['type', 'phone'],
            ])->first();
            if ($contactFieldType) {
                $this->contactFieldPhoneId = $contactFieldType->id;
            } else {
                // TODO
            }
        }

        return $this->contactFieldPhoneId;
    }
}
