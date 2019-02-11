<?php

namespace App\Console\Commands;

use App\Models\User\User;
use App\Helpers\DateHelper;
use App\Models\Contact\Gender;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use Illuminate\Console\Command;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use App\Services\Contact\Address\CreateAddress;
use App\Services\Contact\Reminder\CreateReminder;

class ImportCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {user : email or id of a user} {file : path to the CSV file} {--format=google}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports CSV in Google format to user account';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (is_numeric($this->argument('user'))) {
            $user = User::find($this->argument('user'));
        } else {
            $user = User::where('email', $this->argument('user'))->first();
        }

        if (! file_exists($file)) {
            $this->error('You need to provide a valid file path.');

            return -1;
        }

        if (! $user) {
            $this->error('You need to provide a valid User ID or email address!');

            return -1;
        }

        $this->info("Importing CSV file $file to user {$user->id}");

        // create special gender for this import
        // we don't know which gender all the contacts are, so we need to create a special status for them, as we
        // can't guess whether they are men, women or else.
        $gender = Gender::where('name', config('dav.default_gender'))->first();
        if (! $gender) {
            $gender = new Gender;
            $gender->account_id = $user->account_id;
            $gender->name = config('dav.default_gender');
            $gender->save();
        }

        $first = true;
        $imported = 0;
        if (($handle = fopen($file, 'r')) !== false) {
            try {
                while (($data = fgetcsv($handle)) !== false) {
                    // don't import the columns
                    if ($first) {
                        $first = false;
                        continue;
                    }

                    // if first & last name do not exist skip row
                    if (empty($data[1]) && empty($data[3])) {
                        continue;
                    }

                    $this->csvToContact($data, $user->account_id, $gender->id);

                    $imported++;
                }
            } finally {
                fclose($handle);
            }
        }

        $this->info("Imported {$imported} Contacts");
    }

    /**
     * Create contact.
     */
    private function csvToContact($data, $account_id, $gender_id)
    {
        $contact = new Contact();
        $contact->account_id = $account_id;
        $contact->gender_id = $gender_id;

        if (! empty($data[1])) {
            $contact->first_name = $data[1];    // Given Name
        }

        if (! empty($data[2])) {
            $contact->middle_name = $data[2];   // Additional Name
        }

        if (! empty($data[3])) {
            $contact->last_name = $data[3];     // Family Name
        }

        $street = null;
        if (! empty($data[49])) {
            $street = $data[49];       // address 1 street
        }

        $city = null;
        if (! empty($data[50])) {
            $city = $data[50];         // address 1 city
        }

        $province = null;
        if (! empty($data[52])) {
            $province = $data[52];     // address 1 region (state)
        }

        $postalCode = null;
        if (! empty($data[53])) {
            $postalCode = $data[53];  // address 1 postal code (zip) 53
        }

        if (! empty($data[66])) {
            $contact->job = $data[66];          // organization 1 name 66
        }

        $contact->setAvatarColor();
        $contact->save();

        if (! empty($data[28])) {
            // Email 1 Value
            ContactField::firstOrCreate([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'data' => $data[28],
                'contact_field_type_id' => $this->contactFieldEmailId(),
            ]);
        }

        if ($postalCode || $province || $street || $city) {
            $request = [
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'street' => $street,
                'city' => $city,
                'province' => $province,
                'postal_code' => $postalCode,
            ];

            app(CreateAddress::class)->execute($request);
        }

        if (! empty($data[42])) {
            // Phone 1 Value
            ContactField::firstOrCreate([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'data' => $data[42],
                'contact_field_type_id' => $this->contactFieldPhoneId(),
            ]);
        }

        if (! empty($data[14])) {
            $birthdate = DateHelper::parseDate($data[14]);

            $specialDate = $contact->setSpecialDate('birthdate', $birthdate->year, $birthdate->month, $birthdate->day);

            app(CreateReminder::class)->execute([
                'account_id' => $contact->account_id,
                'contact_id' => $contact->id,
                'initial_date' => $specialDate->date->toDateString(),
                'frequency_type' => 'year',
                'frequency_number' => 1,
                'title' => trans(
                    'people.people_add_birthday_reminder',
                    ['name' => $contact->first_name]
                ),
                'delible' => false,
            ]);
        }

        $contact->updateGravatar();
    }

    /**
     * Get the default contact field email id for the account.
     *
     * @return int
     */
    private function contactFieldEmailId()
    {
        if (! $this->contactFieldEmailId) {
            $contactFieldType = ContactFieldType::where('type', 'email')->first();
            $this->contactFieldEmailId = $contactFieldType->id;
        }

        return $this->contactFieldEmailId;
    }

    /**
     * Get the default contact field phone id for the account.
     *
     * @return int
     */
    private function contactFieldPhoneId()
    {
        if (! $this->contactFieldPhoneId) {
            $contactFieldType = ContactFieldType::where('type', 'phone')->first();
            $this->contactFieldPhoneId = $contactFieldType->id;
        }

        return $this->contactFieldPhoneId;
    }
}
