<?php

namespace App\Jobs;

use App\User;
use App\Contact;
use App\Reminder;
use App\ImportJob;
use App\ImportJobReport;
use App\Country;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Property\ICalendar\DateTime;
use Sabre\VObject\Reader;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
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

                if($vcard->N && ! empty($vcard->N->getParts()[1])) {
                    $contact->first_name = $this->formatValue($vcard->N->getParts()[1]);
                    $contact->middle_name = $this->formatValue($vcard->N->getParts()[2]);
                    $contact->last_name = $this->formatValue($vcard->N->getParts()[0]);
                } else {
                    $contact->first_name = $this->formatValue($vcard->NICKNAME);
                }

                $contact->gender = 'none';
                $contact->is_birthdate_approximate = 'unknown';

                if ($vcard->BDAY && !empty((string) $vcard->BDAY)) {
                    $contact->is_birthdate_approximate = 'exact';
                    $contact->birthdate = new \DateTime((string) $vcard->BDAY);
                }

                $contact->email = $this->formatValue($vcard->EMAIL);
                $contact->phone_number = $this->formatValue($vcard->TEL);

                if($vcard->ADR) {
                    $contact->street = $this->formatValue($vcard->ADR->getParts()[2]);
                    $contact->city = $this->formatValue($vcard->ADR->getParts()[3]);
                    $contact->province = $this->formatValue($vcard->ADR->getParts()[4]);
                    $contact->postal_code = $this->formatValue($vcard->ADR->getParts()[5]);

                    $country = Country::where('country', $vcard->ADR->getParts()[6])
                        ->orWhere('iso', strtolower($vcard->ADR->getParts()[6]))
                        ->first();

                    if ($country) {
                        $contact->country_id = $country->id;
                    }
                }

                $contact->job = $this->formatValue($vcard->ORG);

                $contact->setAvatarColor();

                $contact->save();

                // if birthdate is known, we need to create reminders
                if (! $contact->isBirthdateApproximate()) {
                    $reminder = Reminder::addBirthdayReminder(
                        $contact,
                        trans(
                            'people.people_add_birthday_reminder',
                            ['name' => $contact->getCompleteName()]
                        ),
                        $contact->birthdate
                    );

                    $contact->update([
                        'birthday_reminder_id' => $reminder->id,
                    ]);
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
    function contactHasName(VCard $vcard): bool
    {
        return ! empty($vcard->N->getParts()[1]) || ! empty((string) $vcard->NICKNAME);
    }

    /**
     * Formats and returns a string for the contact
     *
     * @param null|string $value
     * @return null|string
     */
    private function formatValue($value)
    {
        return !empty((string) $value) ? (string) $value : null;
    }

    /**
     * Checks whether a contact already exists for a given account
     *
     * @param VCard $vcard
     * @param User $user
     * @return bool
     */
    private function contactExists(VCard $vcard, $account_id)
    {
        $email = (string) $vcard->EMAIL;

        $contact = Contact::where([
            ['account_id', $account_id],
            ['email', $email]
        ])->first();

        return $email && $contact;
    }

    private function fileImportJobReport(VCard $vcard, $status, $reason = null)
    {
        $name = $this->formatValue($vcard->N->getParts()[1]);
        $name .= ' ' . $this->formatValue($vcard->N->getParts()[2]);
        $name .= ' ' . $this->formatValue($vcard->N->getParts()[0]);
        $name .= ' ' . $this->formatValue($vcard->EMAIL);

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
