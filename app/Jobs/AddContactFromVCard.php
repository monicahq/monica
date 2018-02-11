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

    protected $importJob;
    protected $importedContacts = 0;
    protected $skippedContacts = 0;
    protected $gender;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
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
                if ($this->contactExists($vcard, $this->importJob->account_id)) {
                    $this->skippedContacts++;
                    $this->fileImportJobReport($vcard, self::VCARD_SKIPPED, self::ERROR_CONTACT_EXIST);

                    return;
                }

                // Skip contact if there isn't a first name or a nickname
                if (! $this->contactHasName($vcard)) {
                    $this->skippedContacts++;
                    $this->fileImportJobReport($vcard, self::VCARD_SKIPPED, self::ERROR_CONTACT_DOESNT_HAVE_FIRSTNAME);

                    return;
                }

                $contact = new Contact();
                $contact->account_id = $this->importJob->account_id;

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
                    $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]));
                }

                if ($vcard->ADR) {
                    $address = new Address();
                    $address->street = $this->formatValue($vcard->ADR->getParts()[2]);
                    $address->city = $this->formatValue($vcard->ADR->getParts()[3]);
                    $address->province = $this->formatValue($vcard->ADR->getParts()[4]);
                    $address->postal_code = $this->formatValue($vcard->ADR->getParts()[5]);

                    $country = Country::where('country', $vcard->ADR->getParts()[6])
                        ->orWhere('iso', strtolower($vcard->ADR->getParts()[6]))
                        ->first();

                    if ($country) {
                        $address->country_id = $country->id;
                    }

                    $address->contact_id = $contact->id;
                    $address->account_id = $contact->account_id;
                    $address->save();
                }

                if (! is_null($this->formatValue($vcard->EMAIL))) {
                    // Saves the email
                    $contactFieldType = ContactFieldType::where('type', 'email')
                                                        ->where('account_id', $contact->account_id)
                                                        ->first();

                    if (! empty($contactFieldType)) {
                        $contactField = new ContactField;
                        $contactField->account_id = $contact->account_id;
                        $contactField->contact_id = $contact->id;
                        $contactField->data = $this->formatValue($vcard->EMAIL);
                        $contactField->contact_field_type_id = $contactFieldType->id;
                        $contactField->save();
                    }
                }

                if (! is_null($this->formatValue($vcard->TEL))) {
                    // Saves the phone number
                    $contactFieldType = ContactFieldType::where('type', 'phone')
                                                        ->where('account_id', $contact->account_id)
                                                        ->first();

                    if (! empty($contactFieldType)) {
                        $contactField = new ContactField;
                        $contactField->account_id = $contact->account_id;
                        $contactField->contact_id = $contact->id;
                        $contactField->data = $this->formatValue($vcard->TEL);
                        $contactField->contact_field_type_id = $contactFieldType->id;
                        $contactField->save();
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
        } catch (Exception $e) {
            $this->importJob->contacts_found = $numberOfContactsInTheFile;
            $this->importJob->failed = 1;
            $this->importJob->failed_reason = $e->getMessage();
            $this->importJob->save();

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
     * @return bool
     */
    private function contactExists(VCard $vcard, $account_id)
    {
        $email = (string) $vcard->EMAIL;

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
