<?php

namespace App\Console\Commands;

use App\User;
use App\Gender;
use App\Address;
use App\Contact;
use App\Country;
use App\ContactField;
use App\ContactFieldType;
use Sabre\VObject\Reader;
use Illuminate\Console\Command;
use Sabre\VObject\Component\VCard;
use Illuminate\Filesystem\Filesystem;

class ImportVCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:vcard {user} {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports contacts from vCard files for a specific user';
    protected $gender;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param Filesystem $filesystem
     * @return mixed
     */
    public function handle(Filesystem $filesystem)
    {
        $path = './'.$this->argument('path');

        $user = User::where('email', $this->argument('user'))->first();

        if (! $user) {
            $this->error('You need to provide a valid user email!');

            return;
        }

        if (! $filesystem->exists($path) || $filesystem->extension($path) !== 'vcf') {
            $this->error('The provided vcard file was not found or is not valid!');

            return;
        }

        $matchCount = preg_match_all('/(BEGIN:VCARD.*?END:VCARD)/s', $filesystem->get($path), $matches);

        $this->info("We found {$matchCount} contacts in {$path}.");

        if ($this->confirm('Would you like to import them?', true)) {
            $this->info("Importing contacts from {$path}");

            $this->output->progressStart($matchCount);

            $skippedContacts = 0;

            // create special gender for this import
            // we don't know which gender all the contacts are, so we need to create a special status for them, as we
            // can't guess whether they are men, women or else.
            $this->gender = new Gender;
            $this->gender->account_id = $user->account_id;
            $this->gender->name = 'vCard';
            $this->gender->save();

            collect($matches[0])->map(function ($vcard) {
                return Reader::read($vcard);
            })->each(function (VCard $vcard) use ($user, $skippedContacts) {
                if ($this->contactExists($vcard, $user)) {
                    $this->output->progressAdvance();
                    $skippedContacts++;

                    return;
                }

                // Skip contact if there isn't a first name or a nickname
                if (! $this->contactHasName($vcard)) {
                    $this->output->progressAdvance();
                    $skippedContacts++;

                    return;
                }

                $contact = new Contact();
                $contact->account_id = $user->account_id;

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
                    $contactFieldType = ContactFieldType::where('type', 'email')->first();
                    $contactField = new ContactField;
                    $contactField->account_id = $contact->account_id;
                    $contactField->contact_id = $contact->id;
                    $contactField->data = $this->formatValue($vcard->EMAIL);
                    $contactField->contact_field_type_id = $contactFieldType->id;
                    $contactField->save();
                }

                if (! is_null($this->formatValue($vcard->TEL))) {
                    // Saves the phone number
                    $contactFieldType = ContactFieldType::where('type', 'phone')->first();
                    $contactField = new ContactField;
                    $contactField->account_id = $contact->account_id;
                    $contactField->contact_id = $contact->id;
                    $contactField->data = $this->formatValue($vcard->TEL);
                    $contactField->contact_field_type_id = $contactFieldType->id;
                    $contactField->save();
                }

                $contact->logEvent('contact', $contact->id, 'create');

                $this->output->progressAdvance();
            });

            $this->output->progressFinish();

            $this->info("Successfully imported {$matchCount} contacts and skipped {$skippedContacts}.");
        }
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
    private function contactExists(VCard $vcard, User $user)
    {
        $email = (string) $vcard->EMAIL;

        $contactFieldType = ContactFieldType::where([
            ['account_id', $user->account_id],
            ['type', 'email'],
        ])->first();

        $contactField = null;

        if ($contactFieldType) {
            $contactField = ContactField::where([
                ['account_id', $user->account_id],
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
