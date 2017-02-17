<?php

use App\Kid;
use App\Event;
use App\Contact;
use App\Reminder;
use Carbon\Carbon;
use App\ReminderType;
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
        DB::table('peoples')->delete();
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
            'timezone' => 'America/New_York',
            'remember_token' => str_random(10),
        ]);

        $faker = Faker::create();

        // create a random number of contacts
        $numberOfContacts = rand(3, 34);
        echo 'Generate '.$numberOfContacts.' fake contacts'.PHP_EOL;

        for ($i = 0; $i < $numberOfContacts; $i++) {
            $gender = (rand(1, 2) == 1) ? 'male' : 'female';
            $timezone = 'America/New_York';

            // create contact entry
            $contactID = DB::table('contacts')->insertGetId([
                'account_id' => $accountID,
                'gender' => $gender,
                'first_name' => encrypt($faker->firstName($gender)),
                'middle_name' => (rand(1, 2) == 1) ? encrypt($faker->firstName) : null,
                'last_name' => encrypt($faker->lastName),
            ]);

            $contact = Contact::find($contactID);

            // create people entry
            $peopleID = DB::table('peoples')->insertGetId([
                'api_id' => 'ppl_'.RandomHelper::generateString(20),
                'account_id' => $accountID,
                'type' => 'contact',
                'object_id' => $contactID,
                'sortable_name' => encrypt($contact->getCompleteName()),
            ]);

            $contact->people_id = $peopleID;
            $contact->save();

            // add birthdate for this contact (50% chance that this contact has
            // a birthdate)
            $people = People::find($peopleID);
            if (rand(1, 2) == 1) {
                $date = $faker->date($format = 'Y-m-d', $max = 'now');
                $response = $people->updateBirthdate($date, $timezone);
                $reminderId = $people->addReminderForBirthdate($date, $timezone);
                $eventId = $people->logEvent('reminder', $reminderId, 'update');
            }

            // add email
            if (rand(1, 2) == 1) {
                $response = $people->updateEmailAddress($faker->email);
            }

            // add address
            if (rand(1, 2) == 1) {
                $street = $faker->streetAddress;
                $postalCode = $faker->postcode;
                $province = $faker->state;
                $city = $faker->city;
                $countryID = '1';
                $reponse = $people->updateAddress($street, $postalCode, $province, $city, $countryID);
            }

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
                    $birthdate_approximate = 'false';
                } else {
                    $birthdate_approximate = 'true';
                }

                $significantOtherId = $people->addSignificantOther($firstname, $lastname, $gender, $birthdate_approximate, $birthdate, $age, $timezone);

                $eventId = $people->logEvent('significantother', $significantOtherId, 'create');
            }

            // create kids
            if (rand(1, 3) == 1) {
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
                    $kidId = $people->addKid($name, $gender, $birthdate_approximate, $birthdate, $age, $timezone);

                    $people->incrementKidsNumber();

                    $eventId = $people->logEvent('kid', $kidId, 'create');
                }
            }

            // tasks
            if (rand(1, 2) == 1) {
                foreach (range(1, rand(2, 6)) as $index) {
                    $title = $faker->realText(rand(10, 20));
                    $description = null;
                    if (rand(1, 2) == 1) {
                        $description = $faker->realText(rand(50, 200));
                    }
                    $people->addTask($title, $description);
                }
            }

            // reminders
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(2, 6); $j++) {
                    $title = $faker->realText(rand(10, 20));
                    $description = $faker->realText(rand(50, 200));
                    $startDate = $faker->date('Y-m-d');
                    $a = ['year', 'month', 'week', 'one_time'];
                    $frequencyType = $a[mt_rand(0, count($a) - 1)];

                    $reminder = new Reminder;
                    $reminder->description = encrypt($description);
                    $reminder->next_expected_date = $startDate;
                    $reminder->frequency_number = rand(1, 4);

                    $reminderTypeId = null;
                    if (rand(1, 3) > 1) {
                        $reminder->reminder_type_id = rand(2, 5);
                        $reminderType = ReminderType::find($reminder->reminder_type_id);
                        $reminder->title = encrypt(trans($reminderType->translation_key).' '.$people->getFirstName());
                    } else {
                        $reminder->reminder_type_id = null;
                        $reminder->title = encrypt($title);
                    }

                    $reminder->frequency_type = $frequencyType;
                    $reminder->account_id = $people->account_id;
                    $reminder->people_id = $people->id;
                    $reminder->save();

                    $eventId = $people->logEvent('reminder', $reminder->id, 'create');
                }
            }

            // notes
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 13); $j++) {
                    $body = $faker->realText(rand(40, 80));
                    $noteId = $people->addNote($userId, $body);

                    $eventId = $people->logEvent('note', $noteId, 'create');
                }
            }

            // activities
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 12); $j++) {
                    $activityTypeId = rand(1, 13);
                    $description = null;
                    if (rand(1, 2) == 1) {
                        $description = $faker->realText(rand(40, 80));
                    }
                    $dateItHappened = $faker->dateTime('now', $timezone);
                    $authorId = $userId;

                    $id = $people->addActivity($activityTypeId, $description, $dateItHappened, $authorId);

                    // Add events in the dashboard
                    $event = new Event;
                    $event->account_id = $people->account_id;
                    $event->people_id = $people->id;
                    $event->object_type = 'activity';
                    $event->object_id = $id;
                    $event->nature_of_operation = 'create';
                    $event->save();
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
            'timezone' => 'America/New_York',
            'remember_token' => str_random(10),
        ]);
    }
}
