<?php

namespace App\Services\VCard;

use Ramsey\Uuid\Uuid;
use App\Models\User\User;
use App\Traits\DAVFormat;
use Sabre\VObject\Reader;
use App\Helpers\DateHelper;
use App\Helpers\FormHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Helpers\VCardHelper;
use App\Models\Contact\Note;
use App\Helpers\LocaleHelper;
use App\Services\BaseService;
use function Safe\preg_split;
use App\Helpers\AccountHelper;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Helpers\CountriesHelper;
use Sabre\VObject\ParseException;
use Sabre\VObject\Component\VCard;
use App\Models\Account\AddressBook;
use Illuminate\Support\Facades\Log;
use App\Models\Contact\ContactField;
use App\Services\Contact\Tag\DetachTag;
use App\Models\Contact\ContactFieldType;
use App\Services\Contact\Tag\AssociateTag;
use App\Services\Account\Photo\UploadPhoto;
use App\Services\Contact\Avatar\UpdateAvatar;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Address\CreateAddress;
use App\Services\Contact\Address\UpdateAddress;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Contact\UpdateContact;
use App\Services\Contact\Address\DestroyAddress;
use App\Services\Contact\Contact\UpdateWorkInformation;
use App\Services\Contact\ContactField\CreateContactField;
use App\Services\Contact\ContactField\UpdateContactField;
use App\Services\Contact\ContactField\DestroyContactField;

class ImportVCard extends BaseService
{
    use DAVFormat;

    public const BEHAVIOUR_ADD = 'behaviour_add';
    public const BEHAVIOUR_REPLACE = 'behaviour_replace';

    /** @var array<string,string> */
    protected $errorResults = [
        'ERROR_PARSER' => 'import_vcard_parse_error',
        'ERROR_CONTACT_EXIST' => 'import_vcard_contact_exist',
        'ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME' => 'import_vcard_contact_no_firstname',
    ];

    /**
     * Valids value for frequency type.
     *
     * @var array
     */
    public static $behaviourTypes = [
        self::BEHAVIOUR_ADD, self::BEHAVIOUR_REPLACE,
    ];

    /**
     * The Account id.
     *
     * @var int
     */
    public $accountId;

    /**
     * The User id.
     *
     * @var int
     */
    public $userId;

    /**
     * The contact fields ids.
     *
     * @var array
     */
    protected $contactFields;

    /**
     * The genders that will be associated with imported contacts.
     *
     * @var array<Gender>
     */
    protected $genders;

    /**
     * @var AddressBook|null
     */
    protected $addressBook;

    /**
     * Get the validation rules that apply to the service.
     *
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'user_id' => 'required|integer|exists:users,id',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'entry' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (! is_string($value) && ! is_resource($value) && ! $value instanceof VCard) {
                        $fail($attribute.' must be a string, a resource, or a VCard object.');
                    }
                },
            ],
            'behaviour' => [
                'required',
                Rule::in(self::$behaviourTypes),
            ],
            'addressBookName' => 'nullable|string|exists:addressbooks,name',
            'etag' => 'nullable|string',
        ];
    }

    /**
     * Import one VCard.
     *
     * @param  array  $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->validate($data);

        $account = Account::find($data['account_id']);
        if (AccountHelper::hasReachedContactLimit($account)
            && AccountHelper::hasLimitations($account)
            && ! $account->legacy_free_plan_unlimited_contacts) {
            abort(402);
        }

        User::where('account_id', $data['account_id'])
            ->findOrFail($data['user_id']);

        if ($contactId = Arr::get($data, 'contact_id')) {
            Contact::where('account_id', $data['account_id'])
                ->findOrFail($contactId);
        }

        if ($addressBookName = Arr::get($data, 'addressBookName')) {
            AddressBook::where([
                'account_id' => $data['account_id'],
                'name' => $addressBookName,
            ])->firstOrFail();
        }

        return $this->process($data);
    }

    private function clear()
    {
        $this->contactFields = [];
        $this->genders = [];
        $this->accountId = 0;
        $this->userId = 0;
        $this->addressBook = null;
    }

    /**
     * Process data importation.
     *
     * @param  array  $data
     * @return array
     */
    private function process(array $data): array
    {
        if ($this->accountId !== $data['account_id']) {
            $this->clear();
            $this->accountId = $data['account_id'];
        }
        $this->userId = $data['user_id'];

        if ($addressBookName = Arr::get($data, 'addressBookName')) {
            $this->addressBook = AddressBook::where([
                'account_id' => $data['account_id'],
                'name' => $addressBookName,
            ])->first();
        }

        /**
         * @var VCard|null $entry
         * @var string $vcard
         */
        ['entry' => $entry, 'vcard' => $vcard] = $this->getEntry($data);

        if ($entry === null) {
            return [
                'error' => 'ERROR_PARSER',
                'reason' => $this->errorResults['ERROR_PARSER'],
                'name' => '(unknow)',
            ];
        }

        return $this->processEntry($data, $entry, $vcard);
    }

    /**
     * Process entry importation.
     *
     * @param  array  $data
     * @param  VCard  $entry
     * @param  string  $vcard
     * @return array
     */
    private function processEntry(array $data, VCard $entry, string $vcard): array
    {
        if (! $this->canImportCurrentEntry($entry)) {
            return [
                'error' => 'ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME',
                'reason' => $this->errorResults['ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME'],
                'name' => $this->name($entry),
            ];
        }

        $contactId = Arr::get($data, 'contact_id');
        $contact = $this->getExistingContact($entry, $contactId);

        return $this->processEntryContact($data, $entry, $vcard, $contact);
    }

    /**
     * Process entry importation.
     *
     * @param  array  $data
     * @param  VCard  $entry
     * @param  string  $vcard
     * @param  Contact|null  $contact
     * @return array
     */
    private function processEntryContact(array $data, VCard $entry, string $vcard, ?Contact $contact): array
    {
        $behaviour = $data['behaviour'] ?: self::BEHAVIOUR_ADD;
        if ($contact && $behaviour === self::BEHAVIOUR_ADD) {
            return [
                'contact_id' => $contact->id,
                'error' => 'ERROR_CONTACT_EXIST',
                'reason' => $this->errorResults['ERROR_CONTACT_EXIST'],
                'name' => $this->name($entry),
            ];
        }

        if ($contact) {
            $timestamps = $contact->timestamps;
            $contact->timestamps = false;
        }

        $contact = $this->importEntry($contact, $entry, $vcard, Arr::get($data, 'etag'));

        if (isset($timestamps)) {
            $contact->timestamps = $timestamps;
        }

        return [
            'contact_id' => $contact->id,
            'name' => $this->name($entry),
        ];
    }

    /**
     * @param  array  $data
     * @return array
     */
    private function getEntry($data): array
    {
        $entry = $vcard = $data['entry'];

        if (! $entry instanceof VCard) {
            try {
                $entry = Reader::read($entry, Reader::OPTION_FORGIVING + Reader::OPTION_IGNORE_INVALID_LINES);
            } catch (ParseException $e) {
                return [
                    'entry' => null,
                    'vcard' => $vcard,
                ];
            }
        }

        if ($vcard instanceof VCard) {
            $vcard = $entry->serialize();
        }

        return [
            'entry' => $entry,
            'vcard' => $vcard,
        ];
    }

    /**
     * Get or create the gender called "Vcard" that is associated with all
     * imported contacts.
     *
     * @param  string  $genderCode
     * @return Gender
     */
    private function getGender($genderCode): Gender
    {
        if (! Arr::has($this->genders, $genderCode)) {
            $gender = $this->getGenderByType($genderCode);
            if (! $gender) {
                switch ($genderCode) {
                    case 'M':
                        $gender = $this->getGenderByName(trans('app.gender_male')) ?? $this->getGenderByName(config('dav.default_gender'));
                        break;
                    case 'F':
                        $gender = $this->getGenderByName(trans('app.gender_female')) ?? $this->getGenderByName(config('dav.default_gender'));
                        break;
                    default:
                        $gender = $this->getGenderByName(config('dav.default_gender'));
                        break;
                }
            }

            if (! $gender) {
                $gender = new Gender;
                $gender->account_id = $this->accountId;
                $gender->name = config('dav.default_gender');
                $gender->type = Gender::UNKNOWN;
                $gender->save();
            }

            Arr::set($this->genders, $genderCode, $gender);
        }

        return Arr::get($this->genders, $genderCode);
    }

    /**
     * Get the gender by name.
     *
     * @param  string  $name
     * @return Gender|null
     */
    private function getGenderByName($name)
    {
        return Gender::where([
            'account_id' => $this->accountId,
            'name' => $name,
        ])->first();
    }

    /**
     * Get the gender by type.
     *
     * @param  string  $type
     * @return Gender|null
     */
    private function getGenderByType($type)
    {
        return Gender::where([
            'account_id' => $this->accountId,
            'type' => $type,
        ])->first();
    }

    /**
     * Check whether a contact has a first name or a nickname. If not, contact
     * can not be imported.
     *
     * @param  VCard  $entry
     * @return bool
     */
    private function canImportCurrentEntry(VCard $entry): bool
    {
        return
            $this->hasFirstnameInN($entry) ||
            $this->hasNickname($entry) ||
            $this->hasFN($entry);
    }

    /**
     * @param  VCard  $entry
     * @return bool
     */
    private function hasFirstnameInN(VCard $entry): bool
    {
        return $entry->N !== null && ! empty(Arr::get($entry->N->getParts(), '1'));
    }

    /**
     * @param  VCard  $entry
     * @return bool
     */
    private function hasNICKNAME(VCard $entry): bool
    {
        return ! empty((string) $entry->NICKNAME);
    }

    /**
     * @param  VCard  $entry
     * @return bool
     */
    private function hasFN(VCard $entry): bool
    {
        return ! empty((string) $entry->FN);
    }

    /**
     * Check whether the email is valid.
     *
     * @param  string  $email
     */
    private function isValidEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check whether the contact already exists in the database.
     *
     * @param  VCard  $entry
     * @param  int  $contact_id
     * @return Contact|null
     */
    private function getExistingContact(VCard $entry, $contact_id = null)
    {
        $contact = null;
        if (! is_null($contact_id)) {
            $contact = Contact::where([
                'account_id' => $this->accountId,
                'address_book_id' => $this->addressBook ? $this->addressBook->id : null,
            ])
                ->find($contact_id);
        }

        if (! $contact) {
            $contact = $this->existingUuid($entry);
        }

        if (! $contact) {
            $contact = $this->existingContactWithEmail($entry);
        }

        if (! $contact) {
            $contact = $this->existingContactWithName($entry);
        }

        if ($contact) {
            $contact->timestamps = false;
        }

        return $contact;
    }

    /**
     * Search with email field.
     *
     * @param  VCard  $entry
     * @return Contact|null
     */
    private function existingContactWithEmail(VCard $entry): ?Contact
    {
        if (empty($entry->EMAIL)) {
            return null;
        }

        if ($this->isValidEmail((string) $entry->EMAIL)) {
            $contactField = ContactField::where([
                'account_id' => $this->accountId,
                'contact_field_type_id' => $this->getContactFieldTypeId(ContactFieldType::EMAIL),
            ])->whereIn('data', iterator_to_array($entry->EMAIL))->first();

            // filter contact field
            //  - if no address book selected
            //  - if the address book match the contact's contact field address book
            if ($contactField && (
                    ! $this->addressBook
                    || $contactField->contact->address_book_id === $this->addressBook->id
                )) {
                return $contactField->contact;
            }
        }

        return null;
    }

    /**
     * Search with names fields.
     *
     * @param  VCard  $entry
     * @return Contact|null
     */
    private function existingContactWithName(VCard $entry)
    {
        $contact = [];
        $this->importNames($contact, $entry);

        return Contact::where([
            'account_id' => $this->accountId,
            'first_name' => Arr::get($contact, 'first_name'),
            'middle_name' => Arr::get($contact, 'middle_name'),
            'last_name' => Arr::get($contact, 'last_name'),
            'address_book_id' => $this->addressBook ? $this->addressBook->id : null,
        ])->first();
    }

    /**
     * Search with uuid.
     *
     * @param  VCard  $entry
     * @return Contact|null
     */
    private function existingUuid(VCard $entry): ?Contact
    {
        return ! empty($uuid = (string) $entry->UID) && Uuid::isValid($uuid)
            ? Contact::where([
                'account_id' => $this->accountId,
                'uuid' => $uuid,
                'address_book_id' => $this->addressBook ? $this->addressBook->id : null,
            ])->first()
            : null;
    }

    /**
     * Create the Contact object matching the current entry.
     *
     * @param  Contact|null  $contact
     * @param  VCard  $entry
     * @param  string  $vcard
     * @param  string|null  $etag
     * @return Contact
     */
    private function importEntry(?Contact $contact, VCard $entry, string $vcard, ?string $etag): Contact
    {
        $contact = $this->importGeneralInformation($contact, $entry);

        $this->importPhoto($contact, $entry);
        $this->importWorkInformation($contact, $entry);
        $this->importAddress($contact, $entry);
        $this->importEmail($contact, $entry);
        $this->importTel($contact, $entry);
        $this->importSocialProfile($contact, $entry);
        $this->importCategories($contact, $entry);
        $this->importNote($contact, $entry);

        // Save vcard content
        if ($contact->address_book_id) {
            $contact->vcard = $vcard;
            $contact->distant_etag = $etag;
        }

        $contact->save();

        return $contact;
    }

    /**
     * Import general contact information.
     *
     * @param  Contact|null  $contact
     * @param  VCard  $entry
     * @return Contact
     */
    private function importGeneralInformation(?Contact $contact, VCard $entry): Contact
    {
        $contactData = $this->getContactData($contact);
        $original = $contactData;

        $contactData = $this->importUid($contactData, $entry);
        $contactData = $this->importNames($contactData, $entry);
        $contactData = $this->importGender($contactData, $entry);
        $contactData = $this->importBirthday($contactData, $entry);

        if ($contact !== null && $contactData !== $original) {
            $contact = app(UpdateContact::class)->execute($contactData);
        } else {
            $contact = app(CreateContact::class)->execute($contactData);
        }

        return $contact;
    }

    /**
     * Get contact data.
     *
     * @param  Contact|null  $contact
     * @return array
     */
    private function getContactData(?Contact $contact): array
    {
        $result = [
            'account_id' => $contact ? $contact->account_id : $this->accountId,
            'uuid' => $contact ? $contact->uuid : null,
            'address_book_id' => $this->addressBook ? $this->addressBook->id : null,
            'first_name' => $contact ? $contact->first_name : null,
            'middle_name' => $contact ? $contact->middle_name : null,
            'last_name' => $contact ? $contact->last_name : null,
            'nickname' => $contact ? $contact->nickname : null,
            'gender_id' => $contact ? $contact->gender_id : $this->getGender('O')->id,
            'description' => $contact ? $contact->description : null,
            'is_partial' => $contact ? $contact->is_partial : false,
            'is_birthdate_known' => $contact ? $contact->birthdate !== null : false,
            'is_deceased' => $contact && $contact->is_dead !== null ? $contact->is_dead : false,
            'is_deceased_date_known' => $contact ? $contact->deceasedDate !== null : false,
            'author_id' => $this->userId,
        ];

        if ($contact) {
            $result['contact_id'] = $contact->id;
        }

        if ($result['is_birthdate_known']) {
            if ($result['birthdate_is_age_based'] = $contact->birthdate->is_age_based) {
                $result['birthdate_age'] = now()->diffInYears($contact->birthdate->date, true);
            } else {
                $result['birthdate_day'] = $contact->birthdate->date->day;
                $result['birthdate_month'] = $contact->birthdate->date->month;
                if (! $contact->birthdate->is_year_unknown) {
                    $result['birthdate_year'] = $contact->birthdate->date->year;
                }
            }
        }

        if ($result['is_deceased_date_known'] &&
            ! ($result['birthdate_is_age_based'] = $contact->deceasedDate->is_age_based)) {
            $result['deceased_date_day'] = $contact->deceasedDate->date->day;
            $result['deceased_date_month'] = $contact->deceasedDate->date->month;
            if (! $contact->deceasedDate->is_year_unknown) {
                $result['deceased_date_year'] = $contact->deceasedDate->date->year;
            }
        }

        return $result;
    }

    /**
     * Import names of the contact.
     *
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importNames(array $contactData, VCard $entry): array
    {
        if ($this->hasFirstnameInN($entry)) {
            $contactData = $this->importFromN($contactData, $entry);
        } elseif ($this->hasFN($entry)) {
            $contactData = $this->importFromFN($contactData, $entry);
        } elseif ($this->hasNICKNAME($entry)) {
            $contactData = $this->importFromNICKNAME($contactData, $entry);
        } else {
            throw new \LogicException('Check if you can import entry!');
        }

        return $contactData;
    }

    /**
     * Return the name and email address of the current entry.
     * John Doe Johnny john@doe.com.
     * Only used for report display.
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     *
     * @param  VCard  $entry
     * @return array|string|null|\Illuminate\Contracts\Translation\Translator
     */
    private function name($entry)
    {
        if ($this->hasFirstnameInN($entry)) {
            $parts = $entry->N->getParts();

            $name = '';
            if (! empty(Arr::get($parts, '1'))) {
                $name .= $this->formatValue($parts[1]);
            }
            if (! empty(Arr::get($parts, '2'))) {
                $name .= ' '.$this->formatValue($parts[2]);
            }
            if (! empty(Arr::get($parts, '0'))) {
                $name .= ' '.$this->formatValue($parts[0]);
            }
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
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importFromN(array $contactData, VCard $entry): array
    {
        $parts = $entry->N->getParts();

        $contactData['last_name'] = $this->formatValue(Arr::get($parts, '0'));
        $contactData['first_name'] = $this->formatValue(Arr::get($parts, '1'));
        $contactData['middle_name'] = $this->formatValue(Arr::get($parts, '2'));
        // prefix [3]
        // suffix [4]

        if (! empty($entry->NICKNAME)) {
            $contactData['nickname'] = $this->formatValue($entry->NICKNAME);
        }

        return $contactData;
    }

    /**
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importFromNICKNAME(array $contactData, VCard $entry): array
    {
        $contactData['first_name'] = $this->formatValue($entry->NICKNAME);

        return $contactData;
    }

    /**
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importFromFN(array $contactData, VCard $entry): array
    {
        $fullnameParts = preg_split('/\s+/', $entry->FN, 2);

        $user = User::where('account_id', $this->accountId)
            ->findOrFail($this->userId);

        if (FormHelper::getNameOrderForForms($user) === 'firstname') {
            $contactData['first_name'] = $this->formatValue($fullnameParts[0]);
            if (count($fullnameParts) > 1) {
                $contactData['last_name'] = $this->formatValue($fullnameParts[1]);
            }
        } elseif (count($fullnameParts) > 1) {
            $contactData['last_name'] = $this->formatValue($fullnameParts[0]);
            $contactData['first_name'] = $this->formatValue($fullnameParts[1]);
        } else {
            $contactData['first_name'] = $this->formatValue($fullnameParts[0]);
        }

        if (! empty($entry->NICKNAME)) {
            $contactData['nickname'] = $this->formatValue($entry->NICKNAME);
        }

        return $contactData;
    }

    /**
     * Import uid of the contact.
     *
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importUid(array $contactData, VCard $entry): array
    {
        if (! empty($uuid = (string) $entry->UID) && Uuid::isValid($uuid)) {
            $contactData['uuid'] = $uuid;
        }

        return $contactData;
    }

    /**
     * Import gender of the contact.
     *
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importGender(array $contactData, VCard $entry): array
    {
        if ($entry->GENDER) {
            $contactData['gender_id'] = $this->getGender((string) $entry->GENDER)->id;
        }

        return $contactData;
    }

    /**
     * Import photo of the contact.
     *
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importPhoto(Contact $contact, VCard $entry): void
    {
        if ($entry->PHOTO) {
            if (Str::startsWith((string) $entry->PHOTO, 'https://secure.gravatar.com') || Str::startsWith((string) $entry->PHOTO, 'https://www.gravatar.com')) {
                // Gravatar
                $contact->avatar_gravatar_url = (string) $entry->PHOTO;
            } elseif (! Str::startsWith((string) $entry->PHOTO, 'https://')
                && ! Str::startsWith((string) $entry->PHOTO, 'http://')
                && ($contact->avatar_source != 'photo' || empty($contact->avatar_photo_id))) {
                // Import photo image
                // Skipping in case a photo avatar is already set

                $array = [
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'data' => (string) $entry->PHOTO,
                ];
                if (! is_null($entry->PHOTO['TYPE'])) {
                    /** @var \Sabre\VObject\Parameter */
                    $type = $entry->PHOTO['TYPE'];
                    $array['extension'] = $type->getValue();
                }

                try {
                    $photo = app(UploadPhoto::class)
                        ->execute($array);
                    if (! $photo) {
                        return;
                    }

                    app(UpdateAvatar::class)->execute([
                        'account_id' => $contact->account_id,
                        'contact_id' => $contact->id,
                        'source' => 'photo',
                        'photo_id' => $photo->id,
                    ]);
                } catch (ValidationException $e) {
                    // wrong data
                    Log::error(__CLASS__.' '.__FUNCTION__.': ERROR when UploadPhoto: '.implode(', ', $e->validator->errors()->all()).', PHOTO='.$array['data'], [
                        'data' => $array,
                        'contact_id' => $contact->id,
                        $e,
                    ]);
                }
            }
        }
    }

    /**
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importWorkInformation(Contact $contact, VCard $entry): void
    {
        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'author_id' => $this->userId,
        ];

        if ($entry->ORG) {
            $parts = $entry->ORG->getParts();
            if ($company = Arr::get($parts, '0')) {
                $request['company'] = $this->formatValue($company);
            }
        }

        if ($entry->ROLE) {
            $request['job'] = $this->formatValue($entry->ROLE);
        }

        if ($entry->TITLE) {
            $request['job'] = $this->formatValue($entry->TITLE);
        }

        if (array_key_exists('job', $request) || array_key_exists('company', $request)) {
            app(UpdateWorkInformation::class)->execute($request);
        }
    }

    /**
     * @param  array  $contactData
     * @param  VCard  $entry
     * @return array
     */
    private function importBirthday(array $contactData, VCard $entry): array
    {
        if ($entry->BDAY && ! empty((string) $entry->BDAY)) {
            $bday = (string) $entry->BDAY;
            $is_year_unknown = false;

            if (Str::startsWith($bday, '--')) {
                $bday = '0'.substr($bday, 1);
                $is_year_unknown = true;
            }

            $birthdate = null;
            try {
                $birthdate = DateHelper::parseDate($bday);
            } catch (\Exception $e) {
                // catch any date parse exception
            }

            if (! is_null($birthdate)) {
                $contactData['is_birthdate_known'] = true;
                $contactData['birthdate_is_age_based'] = false;
                $contactData['birthdate_day'] = $birthdate->day;
                $contactData['birthdate_month'] = $birthdate->month;
                $contactData['birthdate_year'] = $is_year_unknown ? null : $birthdate->year;
                $contactData['birthdate_add_reminder'] = true;
                $contactData['is_deceased'] = false;
            }
        }

        return $contactData;
    }

    /**
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importAddress(Contact $contact, VCard $entry): void
    {
        if (! $entry->ADR) {
            return;
        }

        $addresses = $contact->addresses()
                                ->get()
                                ->sortBy('id');

        foreach ($entry->ADR as $adr) {
            $parts = $adr->getParts();
            $addressContent = [
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'street' => $this->formatValue(Arr::get($parts, '2')),
                'city' => $this->formatValue(Arr::get($parts, '3')),
                'province' => $this->formatValue(Arr::get($parts, '4')),
                'postal_code' => $this->formatValue(Arr::get($parts, '5')),
                'country' => CountriesHelper::find(Arr::get($parts, '6')),
                'labels' => preg_split('/,/', (string) $adr['TYPE']),
            ];

            // We assume addresses are in the same order
            $address = $addresses->shift();

            if (is_null($address)) {
                // Address does not exist
                app(CreateAddress::class)->execute($addressContent);
            } else {
                // Address has to be updated
                $address = app(UpdateAddress::class)->execute([
                    'address_id' => $address->id,
                    'name' => $address->name,
                ] +
                    $addressContent
                );
            }
        }

        foreach ($addresses as $address) {
            // Remaining addresses have to be removed
            app(DestroyAddress::class)->execute([
                'account_id' => $contact->account_id,
                'address_id' => $address->id,
            ]);
        }
    }

    /**
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importEmail(Contact $contact, VCard $entry): void
    {
        if (is_null($entry->EMAIL)) {
            return;
        }

        $contactFieldTypeId = $this->getContactFieldTypeId(ContactFieldType::EMAIL);
        if (! $contactFieldTypeId) {
            // Case of contact field type email does not exist
            return;
        }

        $emails = $contact->contactFields()
                            ->email()
                            ->get()
                            ->sortBy('id');

        foreach ($entry->EMAIL as $email) {
            $contactFieldContent = [
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'contact_field_type_id' => $contactFieldTypeId,
                'data' => $this->formatValue((string) $email),
                'labels' => preg_split('/,/', (string) $email['TYPE']),
            ];

            // We assume contact fields are in the same order
            $contactField = $emails->shift();

            if (is_null($contactField)) {
                // Address does not exist
                app(CreateContactField::class)->execute($contactFieldContent);
            } else {
                // Address has to be updated
                app(UpdateContactField::class)->execute([
                    'contact_field_id' => $contactField->id,
                ] +
                    $contactFieldContent
                );
            }
        }

        foreach ($emails as $email) {
            // Remaining emails have to be removed
            app(DestroyContactField::class)->execute([
                'account_id' => $contact->account_id,
                'contact_field_id' => $email->id,
            ]);
        }
    }

    /**
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importNote(Contact $contact, VCard $entry): void
    {
        if (is_null($entry->NOTE)) {
            return;
        }

        $note = Note::create([
            'contact_id' => $contact->id,
            'account_id' => $contact->account_id,
            'body' => $entry->NOTE,
        ]);
    }

    /**
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importTel(Contact $contact, VCard $entry): void
    {
        if (is_null($entry->TEL)) {
            return;
        }

        $contactFieldTypeId = $this->getContactFieldTypeId(ContactFieldType::PHONE);
        if (! $contactFieldTypeId) {
            // Case of contact field type phone does not exist
            return;
        }

        $phones = $contact->contactFields()
                            ->phone()
                            ->get()
                            ->sortBy('id');

        $countryISO = VCardHelper::getCountryISOFromSabreVCard($entry);

        foreach ($entry->TEL as $tel) {
            $data = (string) $tel;
            $data = LocaleHelper::formatTelephoneNumberByISO($data, $countryISO, Str::startsWith($data, '+') ? \libphonenumber\PhoneNumberFormat::INTERNATIONAL : \libphonenumber\PhoneNumberFormat::NATIONAL);

            $contactFieldContent = [
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'contact_field_type_id' => $contactFieldTypeId,
                'data' => $this->formatValue($data),
                'labels' => preg_split('/,/', (string) $tel['TYPE']),
            ];

            // We assume contact fields are in the same order
            $phone = $phones->shift();

            if (is_null($phone)) {
                // Address does not exist
                app(CreateContactField::class)->execute($contactFieldContent);
            } else {
                // Address has to be updated
                app(UpdateContactField::class)->execute([
                    'contact_field_id' => $phone->id,
                ] +
                    $contactFieldContent
                );
            }
        }

        foreach ($phones as $phone) {
            // Remaining phones have to be removed
            app(DestroyContactField::class)->execute([
                'account_id' => $contact->account_id,
                'contact_field_id' => $phone->id,
            ]);
        }
    }

    /**
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importSocialProfile(Contact $contact, VCard $entry): void
    {
        if (is_null($entry->socialProfile)) {
            return;
        }

        foreach ($entry->socialProfile as $socialProfile) {
            $type = $socialProfile['type'];
            $contactFieldTypeId = null;
            $data = null;
            switch ((string) $type) {
                case 'facebook':
                    $contactFieldTypeId = $this->getContactFieldTypeId('Facebook');
                    $data = str_replace('https://www.facebook.com/', '', $this->formatValue((string) $socialProfile));
                    break;
                case 'twitter':
                    $contactFieldTypeId = $this->getContactFieldTypeId('Twitter');
                    $data = str_replace('https://twitter.com/', '', $this->formatValue((string) $socialProfile));
                    break;
                case 'whatsapp':
                    $contactFieldTypeId = $this->getContactFieldTypeId('Whatsapp');
                    $data = str_replace('https://wa.me/', '', $this->formatValue((string) $socialProfile));
                    break;
                case 'telegram':
                    $contactFieldTypeId = $this->getContactFieldTypeId('Telegram');
                    $data = str_replace('http://t.me/', '', $this->formatValue((string) $socialProfile));
                    break;
                case 'linkedin':
                    $contactFieldTypeId = $this->getContactFieldTypeId('LinkedIn');
                    $data = str_replace('http://www.linkedin.com/in/', '', $this->formatValue((string) $socialProfile));
                    break;
                default:
                    // Not supported
                    break;
            }

            if (! is_null($contactFieldTypeId) && ! is_null($data)) {
                ContactField::firstOrCreate([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'data' => $data,
                    'contact_field_type_id' => $contactFieldTypeId,
                ]);
            }
        }
    }

    /**
     * Get the contact field type id for the $type.
     *
     * @param  string  $type  The type of the ContactFieldType, or the name
     * @return int|null
     */
    private function getContactFieldTypeId(string $type)
    {
        if (! Arr::has($this->contactFields, $type)) {
            $contactFieldType = ContactFieldType::where([
                'account_id' => $this->accountId,
                'type' => $type,
            ])->first();

            if (is_null($contactFieldType)) {
                $contactFieldType = ContactFieldType::where([
                    'account_id' => $this->accountId,
                    'name' => $type,
                ])->first();
            }

            Arr::set($this->contactFields, $type, $contactFieldType != null ? $contactFieldType->id : null);
        }

        return Arr::get($this->contactFields, $type);
    }

    /**
     * Import the categories as tags.
     *
     * @param  Contact  $contact
     * @param  VCard  $entry
     * @return void
     */
    private function importCategories(Contact $contact, VCard $entry)
    {
        $tags = [];
        foreach ($contact->tags as $tag) {
            $tags[$tag->name] = $tag->id;
        }

        if (! is_null($entry->CATEGORIES)) {
            $categories = preg_split('/,/', $entry->CATEGORIES);

            foreach ($categories as $category) {
                $name = (string) $category;
                if (isset($tags[$name])) {
                    unset($tags[$name]);
                } else {
                    app(AssociateTag::class)->execute([
                        'account_id' => $contact->account_id,
                        'contact_id' => $contact->id,
                        'name' => $name,
                    ]);
                }
            }
        }

        foreach ($tags as $tag) {
            app(DetachTag::class)->execute([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'tag_id' => $tag,
            ]);
        }
    }
}
