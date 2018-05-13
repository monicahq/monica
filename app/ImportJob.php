<?php

namespace App;

use Exception;
use Sabre\VObject\Reader;
use App\Helpers\CountriesHelper;
use Sabre\VObject\Component\VCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * @property Account $account
 * @property User $user
 */
class ImportJob extends Model
{
    const VCARD_SKIPPED = 1;
    const VCARD_IMPORTED = 0;

    const ERROR_CONTACT_EXIST = 'import_vcard_contact_exist';
    const ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME = 'import_vcard_contact_no_firstname';

    const BEHAVIOUR_ADD = 'behaviour_add';
    const BEHAVIOUR_REPLACE = 'behaviour_replace';

    protected $table = 'import_jobs';

    /**
     * The physical vCard file on disk.
     */
    public $physicalFile;

    /**
     * All individual entries in the vCard file.
     */
    public $entries;

    /**
     * The "Vcard" gender that will be associated with all imported contacts.
     *
     * @var \App\Gender
     */
    public $gender;

    /**
     * The current entry that is being processed.
     *
     * @var VCard
     */
    public $currentEntry;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['started_at', 'ended_at'];

    /**
     * The contact field email object.
     *
     * @var array
     */
    public $contactFieldEmailId;

    /**
     * The contact field phone object.
     *
     * @var array
     */
    public $contactFieldPhoneId;

    /**
     * Get the account record associated with the gift.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user record associated with the gift.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the import jobs reports records associated with the account.
     *
     * @return HasMany
     */
    public function importJobReports()
    {
        return $this->hasMany(ImportJobReport::class);
    }

    /**
     * Process an import job.
     *
     * @return [type] [description]
     */
    public function process($behaviour = self::BEHAVIOUR_ADD)
    {
        $this->initJob();

        $this->getPhysicalFile();

        $this->getEntries();

        $this->getSpecialGender();

        $this->processEntries($behaviour);

        $this->deletePhysicalFile();

        $this->endJob();
    }

    /**
     * Perform preliminary steps to start the import job.
     *
     * @return void
     */
    public function initJob(): void
    {
        $this->started_at = now();
        $this->contacts_imported = 0;
        $this->contacts_skipped = 0;
        $this->save();
    }

    /**
     * Perform the steps to finalize the import job.
     *
     * @return void
     */
    public function endJob(): void
    {
        $this->ended_at = now();
        $this->save();
    }

    /**
     * Get or create the gender called "Vcard" that is associated with all
     * imported contacts.
     *
     * @return \App\Gender
     */
    public function getSpecialGender()
    {
        $this->gender = \App\Gender::where('name', 'vCard')->first();

        if (! $this->gender) {
            $this->gender = new \App\Gender;
            $this->gender->account_id = $this->account_id;
            $this->gender->name = 'vCard';
            $this->gender->save();
        }
    }

    /**
     * Mark the import job as failed.
     *
     * @param  string $reason
     * @return Exception
     */
    public function fail(string $reason)
    {
        $this->failed = true;
        $this->failed_reason = $reason;
        $this->endJob();
    }

    /**
     * Get the physical file (the vCard file).
     *
     * @return $this
     */
    public function getPhysicalFile()
    {
        try {
            $this->physicalFile = Storage::disk('public')->get($this->filename);
        } catch (FileNotFoundException $exception) {
            $this->fail(trans('settings.import_vcard_file_not_found'));
        }

        return $this;
    }

    /**
     * Delete the physical file from the disk.
     *
     * @return $this
     */
    public function deletePhysicalFile()
    {
        if (! Storage::disk('public')->delete($this->filename)) {
            $this->fail(trans('settings.import_vcard_file_not_found'));
        }
    }

    /**
     * Get the number of matches in the vCard file.
     *
     * @return void
     */
    public function getEntries()
    {
        $this->contacts_found = preg_match_all('/(BEGIN:VCARD.*?END:VCARD)/s',
                                                $this->physicalFile,
                                                $this->entries);

        $this->contacts_found = count($this->entries[0]);

        if ($this->contacts_found == 0) {
            $this->fail(trans('settings.import_vcard_file_no_entries'));
        }
    }

    /**
     * Process all entries contained in the vCard file.
     *
     * @return
     */
    public function processEntries($behaviour = self::BEHAVIOUR_ADD)
    {
        collect($this->entries[0])->map(function ($vcard) {
            return Reader::read($vcard);
        })->each(function (VCard $vCard) use ($behaviour) {
            $this->currentEntry = $vCard;
            $this->processSingleEntry($behaviour);
        });
    }

    /**
     * Process a single vCard entry.
     *
     * @param  VCard  $vCard
     * @return [type]        [description]
     */
    public function processSingleEntry($behaviour = self::BEHAVIOUR_ADD)
    {
        if (! $this->checkImportFeasibility()) {
            $this->skipEntry(self::ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME);

            return;
        }

        $contact = $this->existingContact();
        if ($contact && $behaviour === self::BEHAVIOUR_ADD) {
            $this->skipEntry(self::ERROR_CONTACT_EXIST);

            return;
        }

        $this->createContactFromCurrentEntry($contact);
    }

    /**
     * Skip the current entry.
     *
     * @param  string $reason
     * @return void
     */
    public function skipEntry($reason = null): void
    {
        $this->fileImportJobReport(self::VCARD_SKIPPED, $reason);
        $this->contacts_skipped++;
    }

    /**
     * Check whether a contact has a first name or a nickname. If not, contact
     * can not be imported.
     *
     * @param VCard $vcard
     * @return bool
     */
    public function checkImportFeasibility(): bool
    {
        if (is_null($this->currentEntry->N)) {
            return false;
        }

        return ! empty($this->currentEntry->N->getParts()[1]) || ! empty((string) $this->currentEntry->NICKNAME);
    }

    /**
     * Check whether the email is valid.
     *
     * @param string $email
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check whether the contact already exists in the database.
     *
     * @return Contact|null
     */
    public function existingContact()
    {
        if (is_null($this->currentEntry->EMAIL)) {
            return;
        }

        $email = (string) $this->currentEntry->EMAIL;

        if (! $this->isValidEmail($email)) {
            return;
        }

        $contactFieldType = \App\ContactFieldType::where([
            ['account_id', $this->account_id],
            ['type', 'email'],
        ])->first();

        if ($contactFieldType) {
            $contactField = \App\ContactField::where([
                ['account_id', $this->account_id],
                ['contact_field_type_id', $contactFieldType->id],
            ])->whereIn('data', iterator_to_array($this->currentEntry->EMAIL))->first();

            if ($contactField) {
                return $contactField->contact;
            }
        }
    }

    /**
     * File an import job report for the current entry.
     *
     * @param  bool $status
     * @param  string $reason
     * @return void
     */
    public function fileImportJobReport($status, $reason = null): void
    {
        $name = $this->name();

        $importJobReport = new \App\ImportJobReport;
        $importJobReport->account_id = $this->account_id;
        $importJobReport->user_id = $this->user_id;
        $importJobReport->import_job_id = $this->id;
        $importJobReport->contact_information = trim($name);
        $importJobReport->skipped = $status;
        $importJobReport->skip_reason = $reason;
        $importJobReport->save();
    }

    /**
     * Return the name and email address of the current entry.
     * John Doe Johnny john@doe.com.
     *
     * @return string
     */
    public function name(): string
    {
        if (is_null($this->currentEntry->N)) {
            return trans('settings.import_vcard_unknown_entry');
        }

        $name = $this->formatValue($this->currentEntry->N->getParts()[1]);
        $name .= ' '.$this->formatValue($this->currentEntry->N->getParts()[2]);
        $name .= ' '.$this->formatValue($this->currentEntry->N->getParts()[0]);
        $name .= ' '.$this->formatValue($this->currentEntry->EMAIL);

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
     * @return Contact
     */
    public function createContactFromCurrentEntry($contact = null)
    {
        if (! $contact) {
            $contact = new \App\Contact;
            $contact->account_id = $this->account_id;
            $contact->gender_id = $this->gender->id;
            $contact->save();
        }

        $this->importNames($contact);
        $this->importWorkInformation($contact);
        $this->importBirthday($contact);
        $this->importAddress($contact);
        $this->importEmail($contact);
        $this->importTel($contact);

        $this->contacts_imported++;
        $this->fileImportJobReport(self::VCARD_IMPORTED);

        $contact->setAvatarColor();
        $contact->save();

        return $contact;
    }

    /**
     * Import names of the contact.
     *
     * @param Contact $contact
     * @return void
     */
    public function importNames(\App\Contact $contact): void
    {
        if ($this->currentEntry->N && ! empty($this->currentEntry->N->getParts()[1])) {
            $contact->first_name = $this->formatValue($this->currentEntry->N->getParts()[1]);
            $contact->middle_name = $this->formatValue($this->currentEntry->N->getParts()[2]);
            $contact->last_name = $this->formatValue($this->currentEntry->N->getParts()[0]);
        } else {
            $contact->first_name = $this->formatValue($this->currentEntry->NICKNAME);
        }
    }

    /**
     * @param Contact $contact
     * @return void
     */
    public function importWorkInformation(\App\Contact $contact): void
    {
        if ($this->currentEntry->ORG) {
            $contact->company = $this->formatValue($this->currentEntry->ORG);
        }

        if ($this->currentEntry->ROLE) {
            $contact->job = $this->formatValue($this->currentEntry->ROLE);
        }

        if ($this->currentEntry->TITLE) {
            $contact->job = $this->formatValue($this->currentEntry->TITLE);
        }
    }

    /**
     * @param Contact $contact
     * @return void
     */
    public function importBirthday(\App\Contact $contact): void
    {
        if ($this->currentEntry->BDAY && ! empty((string) $this->currentEntry->BDAY)) {
            $birthdate = new \DateTime((string) $this->currentEntry->BDAY);

            $specialDate = $contact->setSpecialDate('birthdate', $birthdate->format('Y'), $birthdate->format('m'), $birthdate->format('d'));
            $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
        }
    }

    /**
     * @param Contact $contact
     * @return void
     */
    public function importAddress(\App\Contact $contact): void
    {
        if (! $this->currentEntry->ADR) {
            return;
        }

        foreach ($this->currentEntry->ADR as $adr) {
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
     * @return void
     */
    public function importEmail(\App\Contact $contact): void
    {
        if (is_null($this->currentEntry->EMAIL)) {
            return;
        }

        foreach ($this->currentEntry->EMAIL as $email) {
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
     * @return void
     */
    public function importTel(\App\Contact $contact): void
    {
        if (is_null($this->currentEntry->TEL)) {
            return;
        }

        foreach ($this->currentEntry->TEL as $tel) {
            ContactField::firstOrCreate([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'data' => $this->formatValue($tel),
                'contact_field_type_id' => $this->contactFieldPhoneId(),
            ]);
        }
    }

    private function contactFieldEmailId()
    {
        if (! $this->contactFieldEmailId) {
            $contactFieldType = \App\ContactFieldType::where('type', 'email')->first();
            $this->contactFieldEmailId = $contactFieldType->id;
        }

        return $this->contactFieldEmailId;
    }

    private function contactFieldPhoneId()
    {
        if (! $this->contactFieldPhoneId) {
            $contactFieldType = \App\ContactFieldType::where('type', 'phone')->first();
            $this->contactFieldPhoneId = $contactFieldType->id;
        }

        return $this->contactFieldPhoneId;
    }
}
