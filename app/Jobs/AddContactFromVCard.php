<?php

namespace App\Jobs;

use App\User;
use App\Gender;
use App\Address;
use App\Contact;
use App\Country;
use App\ImportJob;
use App\ContactField;
use App\ImportJobReport;
use App\ContactFieldType;
use Sabre\VObject\Reader;
use Illuminate\Bus\Queueable;
use Sabre\VObject\Component\VCard;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddContactFromVCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const VCARD_SKIPPED = 1;
    const VCARD_IMPORTED = 0;

    const ERROR_CONTACT_EXIST = 'import_vcard_contact_exist';
    const ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME = 'import_vcard_contact_no_firstname';

    const BEHAVIOUR_ADD = 'behaviour_add';
    const BEHAVIOUR_REPLACE = 'behaviour_replace';

    protected $importJob;
    protected $behaviour;
    protected $importedContacts = 0;
    protected $skippedContacts = 0;
    protected $gender;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportJob $importJob, $behaviour = self::BEHAVIOUR_ADD)
    {
        $this->importJob = $importJob;
        $this->behaviour = $behaviour;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $numberOfContactsInTheFile = preg_match_all('/(BEGIN:VCARD.*?END:VCARD)/s', Storage::disk('public')->get($this->importJob->filename), $matches);

            $this->importJob->started_at = \Carbon\Carbon::now();

            // create special gender for this import
            // we don't know which gender all the contacts are, so we need to create a special status for them, as we
            // can't guess whether they are men, women or else.
            $this->gender = new Gender;
            $this->gender->account_id = $this->importJob->account_id;
            $this->gender->name = 'vCard';
            $this->gender->save();

            collect($matches[0])->map(function ($vcard) {
                return Reader::read($vcard);
            })->each(function (VCard $vcard) {

                // Skip contact if there isn't a first name or a nickname
                if (! $this->contactHasName($vcard)) {
                    $this->skippedContacts++;
                    $this->fileImportJobReport($vcard, self::VCARD_SKIPPED, self::ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME);

                    return;
                }

                $contact = $this->existingContact($vcard, $this->importJob->account_id);
                if ($contact && $this->behaviour === self::BEHAVIOUR_ADD) {
                    $this->skippedContacts++;
                    $this->fileImportJobReport($vcard, self::VCARD_SKIPPED, self::ERROR_CONTACT_EXIST);

                    return;
                }

                if (! $contact) {
                    $contact = new Contact();
                    $contact->account_id = $this->importJob->account_id;
                }

                if ($vcard->N && ! empty($vcard->N->getParts()[1])) {
                    $contact->first_name = $this->formatValue($vcard->N->getParts()[1]);
                    $contact->middle_name = $this->formatValue($vcard->N->getParts()[2]);
                    $contact->last_name = $this->formatValue($vcard->N->getParts()[0]);
                } else {
                    $contact->first_name = $this->formatValue($vcard->NICKNAME);
                }

                $contact->gender_id = $this->gender->id;

                $contact->job = $this->formatValue($vcard->ORG);

                $contact->setAvatarColor();

                $contact->save();

                if ($vcard->BDAY && ! empty((string) $vcard->BDAY)) {
                    $birthdate = new \DateTime((string) $vcard->BDAY);

                    $specialDate = $contact->setSpecialDate('birthdate', $birthdate->format('Y'), $birthdate->format('m'), $birthdate->format('d'));
                    $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                }

                if ($vcard->ADR) {
                    foreach ($vcard->ADR as $adr) {

                        $country = Country::where('country', $adr->getParts()[6])
                            ->orWhere('iso', strtolower($adr->getParts()[6]))
                            ->first();

                        Address::firstOrCreate([
                            'account_id' => $contact->account_id,
                            'contact_id' => $contact->id,
                            'street' => $this->formatValue($adr->getParts()[2]),
                            'city' => $this->formatValue($adr->getParts()[3]),
                            'province' => $this->formatValue($adr->getParts()[4]),
                            'postal_code' => $this->formatValue($adr->getParts()[5]),
                            'country_id' => $country ? $country->id : null,
                        ]);
                    }

                }

                if ($vcard->EMAIL) {
                    // Saves the email
                    $contactFieldType = ContactFieldType::where('type', 'email')
                                                        ->where('account_id', $contact->account_id)
                                                        ->first();

                    if ($contactFieldType) {
                        foreach ($vcard->EMAIL as $data) {
                            ContactField::firstOrCreate([
                                'account_id' => $contact->account_id,
                                'contact_id' => $contact->id,
                                'data' => $this->formatValue($data),
                                'contact_field_type_id' => $contactFieldType->id,
                            ]);
                        }
                    }

                }

                if ($vcard->TEL) {
                    // Saves the phone number
                    $contactFieldType = ContactFieldType::where('type', 'phone')
                                                        ->where('account_id', $contact->account_id)
                                                        ->first();

                    if (! empty($contactFieldType)) {
                        foreach ($vcard->TEL as $data) {
                            ContactField::firstOrCreate([
                                'account_id' => $contact->account_id,
                                'contact_id' => $contact->id,
                                'data' => $this->formatValue($data),
                                'contact_field_type_id' => $contactFieldType->id,
                            ]);
                        }

                    }
                }

                $this->importedContacts++;

                $this->fileImportJobReport($vcard, self::VCARD_IMPORTED);

                $contact->logEvent('contact', $contact->id, 'create');
            });

            $this->importJob->contacts_found = $numberOfContactsInTheFile;
            $this->importJob->contacts_skipped = $this->skippedContacts;
            $this->importJob->contacts_imported = $this->importedContacts;
            $this->importJob->ended_at = \Carbon\Carbon::now();
            $this->importJob->save();
        } catch (\Exception $e) {
            $this->importJob->contacts_found = $numberOfContactsInTheFile;
            $this->importJob->contacts_skipped = $this->skippedContacts;
            $this->importJob->contacts_imported = $this->importedContacts;
            $this->importJob->failed = 1;
            $this->importJob->failed_reason = $e->getMessage();
            $this->importJob->ended_at = \Carbon\Carbon::now();
            $this->importJob->save();

            logger($e);

            Storage::disk('public')->delete($this->importJob->filename);
        }

        // Delete the vCard file no matter what
        Storage::disk('public')->delete($this->importJob->filename);
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
     * @param User $user
     * @return Contact|null
     */
    private function existingContact(VCard $vcard, $account_id)
    {
        $contactFieldType = ContactFieldType::where([
            ['account_id', $account_id],
            ['type', 'email'],
        ])->first();

        if ($vcard->EMAIL && $contactFieldType) {
            $contactField = ContactField::where([
                ['account_id', $account_id],
                ['contact_field_type_id', $contactFieldType->id],
            ])->whereIn('data', iterator_to_array($vcard->EMAIL))->first();

            return $contactField->contact;
        }
    }

    private function fileImportJobReport(VCard $vcard, $status, $reason = null)
    {
        $name = $this->formatValue($vcard->N->getParts()[1]);
        $name .= ' '.$this->formatValue($vcard->N->getParts()[2]);
        $name .= ' '.$this->formatValue($vcard->N->getParts()[0]);
        $name .= ' '.$this->formatValue($vcard->EMAIL);

        $importJobReport = new ImportJobReport;
        $importJobReport->account_id = $this->importJob->account_id;
        $importJobReport->user_id = $this->importJob->user_id;
        $importJobReport->import_job_id = $this->importJob->id;
        $importJobReport->contact_information = trim($name);
        $importJobReport->skipped = $status;
        $importJobReport->skip_reason = $reason;
        $importJobReport->save();
    }
}
