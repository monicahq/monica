<?php

use App\Kid;
use App\Event;
use App\Contact;
use App\Reminder;
use Carbon\Carbon;
use Faker\Factory as Faker;
use App\Helpers\RandomHelper;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class FakeContentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // truncate all the tables
        DB::table('accounts')->delete();
        DB::table('users')->delete();
        DB::table('contacts')->delete();
        DB::table('reminders')->delete();
        DB::table('significant_others')->delete();
        DB::table('kids')->delete();
        DB::table('tasks')->delete();
        DB::table('notes')->delete();
        DB::table('activities')->delete();

        // populate account table
        $accountID = DB::table('accounts')->insertGetId([
            'api_key' => str_random(30),
        ]);

        // populate user table
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountID,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
            'timezone' => config('app.timezone'),
            'remember_token' => str_random(10),
        ]);

        $faker = Faker::create();

        // create a random number of contacts
        $numberOfContacts = rand(3, 100);
        echo 'Generating '.$numberOfContacts.' fake contacts'.PHP_EOL;

        for ($i = 0; $i < $numberOfContacts; $i++) {
            $timezone = config('app.timezone');
            $gender = (rand(1, 2) == 1) ? 'male' : 'female';

            // create contact entry
            $contactID = DB::table('contacts')->insertGetId([
                'account_id' => $accountID,
                'gender' => $gender,
                'first_name' => $faker->firstName($gender),
                'middle_name' => (rand(1, 2) == 1) ? $faker->firstName : null,
                'last_name' => (rand(1, 2) == 1) ? $faker->lastName : null,
            ]);

            $contact = Contact::find($contactID);
            $contact->setAvatarColor();

            // add birthdate for this contact

            // add email
            if (rand(1, 2) == 1) {
                $contact->email = $faker->email;
            }

            // add phonenumber
            if (rand(1, 2) == 1) {
                $contact->phone_number = $faker->phoneNumber;
            }

            // add address
            if (rand(1, 2) == 1) {
                $contact->street = $faker->streetAddress;
                $contact->postal_code = $faker->postcode;
                $contact->province = $faker->state;
                $contact->city = $faker->city;
                $countryID = '1';
            }

            // add food preferencies
            if (rand(1, 2) == 1) {
                $contact->food_preferencies = $faker->realText();
            }

            $contact->save();

            // create significant other data
            if (rand(1, 3) == 1) {
                $gender = (rand(1, 2) == 1) ? 'male' : 'female';
                $firstname = $faker->firstName($gender);
                if (rand(1, 2) == 1) {
                    $lastname = null;
                } else {
                    $lastname = $faker->lastName($gender);
                }
                $birthdate = $faker->date($format = 'Y-m-d', $max = 'now');
                $age = rand(18, 78);
                if (rand(1, 2) == 1) {
                    $birthdate_approximate = 'unknown';
                } else {
                    $birthdate_approximate = 'exact';
                }

                $significantOtherId = $contact->addSignificantOther($firstname, $gender, $birthdate_approximate, $birthdate, $age, $timezone);
            }

            // // create kids
            if (rand(1, 2) == 1) {
                foreach (range(1, rand(2, 6)) as $index) {
                    $gender = (rand(1, 2) == 1) ? 'male' : 'female';
                    $name = $faker->firstName($gender);
                    $birthdate = $faker->date($format = 'Y-m-d', $max = 'now');
                    $age = rand(2, 14);
                    if (rand(1, 2) == 1) {
                        $birthdate_approximate = 'false';
                    } else {
                        $birthdate_approximate = 'true';
                    }
                    $kidId = $contact->addKid($name, $gender, $birthdate_approximate, $birthdate, $age, $timezone);
                }
            }

            // // notes
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 13); $j++) {
                    $body = $faker->realText(rand(40, 500));
                    $noteId = $contact->addNote($body);
                }
            }
        }

        // create the second test, blank account
        $accountID = DB::table('accounts')->insertGetId([
            'api_key' => str_random(30),
        ]);

        // populate user table
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountID,
            'first_name' => 'Blank',
            'last_name' => 'State',
            'email' => 'blank@blank.com',
            'password' => bcrypt('blank'),
            'timezone' => config('app.timezone'),
            'remember_token' => str_random(10),
        ]);
    }
}
