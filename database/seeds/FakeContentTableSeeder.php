<?php

use App\Account;
use App\Contact;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class FakeContentTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // populate account table
        $accountID = DB::table('accounts')->insertGetId([
            'api_key' => str_random(30),
        ]);

        $account = Account::find($accountID);
        $account->populateContactFieldTypeTable();

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
        $numberOfContacts = rand(30, 100);
        echo 'Generating '.$numberOfContacts.' fake contacts'.PHP_EOL;

        for ($i = 0; $i < $numberOfContacts; $i++) {
            $timezone = config('app.timezone');
            $gender = (rand(1, 2) == 1) ? 'male' : 'female';

            // create contact entry
            $contactID = DB::table('contacts')->insertGetId([
                'account_id' => $accountID,
                'gender' => $gender,
                'first_name' => $faker->firstName($gender),
                'last_name' => (rand(1, 2) == 1) ? $faker->lastName : null,
            ]);

            $contact = Contact::find($contactID);
            $contact->setAvatarColor();

            // add food preferencies
            if (rand(1, 2) == 1) {
                $contact->food_preferencies = $faker->realText();
            }

            // deceased?
            if (rand(1, 7) == 1) {
                $contact->is_dead = true;

                if (rand(1,3) == 1) {
                    $deceasedDate = $faker->dateTimeThisCentury();

                    if (rand(1, 2) == 1) {
                        // add a date where we don't know the year
                        $specialDate = $contact->setSpecialDate('deceased_date', 0, $deceasedDate->format('m'), $deceasedDate->format('d'));
                    } else {
                        // add a date where we know the year
                        $specialDate = $contact->setSpecialDate('deceased_date', $deceasedDate->format('Y'), $deceasedDate->format('m'), $deceasedDate->format('d'));
                    }
                    $newReminder = $specialDate->setReminder('year', 1);
                    $newReminder->title = trans('people.deceased_reminder_title', ['name' => $contact->first_name]);
                    $newReminder->save();
                }
            }

            $contact->save();

            // add birthday
            if (rand(1, 2) == 1) {
                $birthdate = $faker->dateTimeThisCentury();

                if (rand(1, 2) == 1) {
                    if (rand(1, 2) == 1) {
                        // add a date where we don't know the year
                        $specialDate = $contact->setSpecialDate('birthdate', 0, $birthdate->format('m'), $birthdate->format('d'));
                    } else {
                        // add a date where we know the year
                        $specialDate = $contact->setSpecialDate('birthdate', $birthdate->format('Y'), $birthdate->format('m'), $birthdate->format('d'));
                    }

                    $newReminder = $specialDate->setReminder('year', 1);
                    $newReminder->title = trans('people.people_add_birthday_reminder', ['name' => $contact->first_name]);
                    $newReminder->save();
                } else {
                    // add a birthdate based on an approximate age
                    $specialDate = $contact->setSpecialDateFromAge('birthdate', rand(10, 100));
                }
            }

            // add first met information
            if (rand(1, 2) == 1) {
                $contact->first_met_where = $faker->realText(20);
            }

            if (rand(1, 2) == 1) {
                $contact->first_met_additional_info = $faker->realText(20);
            }

            if (rand(1, 2) == 1) {
                $firstMetDate = $faker->dateTimeThisCentury();

                if (rand(1, 2) == 1) {
                    // add a date where we don't know the year
                    $specialDate = $contact->setSpecialDate('first_met', 0, $firstMetDate->format('m'), $firstMetDate->format('d'));
                } else {
                    // add a date where we know the year
                    $specialDate = $contact->setSpecialDate('first_met', $firstMetDate->format('Y'), $firstMetDate->format('m'), $firstMetDate->format('d'));
                }
                $newReminder = $specialDate->setReminder('year', 1);
                $newReminder->title = trans('people.introductions_reminder_title', ['name' => $contact->first_name]);
                $newReminder->save();
            }

            if (rand(1, 2) == 1) {
                do {
                    $rand = rand(1, $numberOfContacts);
                } while(in_array($rand, array($contact->id)));

                $contact->first_met_through_contact_id = $rand;
            }

            $contact->save();

            // create kids
            if (rand(1, 2) == 1) {
                foreach (range(1, rand(2, 6)) as $index) {
                    $gender = (rand(1, 2) == 1) ? 'male' : 'female';
                    $name = $faker->firstName($gender);
                    $birthdate = $faker->date($format = 'Y-m-d', $max = 'now');
                    $age = rand(2, 14);
                    if (rand(1, 2) == 1) {
                        $birthdate_approximate = 'unknown';
                    } else {
                        $birthdate_approximate = 'exact';
                    }

                    $childID = DB::table('contacts')->insertGetId([
                        'account_id' => $contact->account_id,
                        'gender' => $gender,
                        'first_name' => $name,
                        'last_name' => (rand(1, 2) == 1) ? $faker->lastName : $contact->last_name,
                    ]);

                    $contact->offsprings()->create(
                        [
                            'account_id' => $contact->account_id,
                            'contact_id' => $childID,
                            'is_the_child_of' => $contact->id,
                        ]
                    );
                }
            }

            // notes
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 13); $j++) {
                    $note = $contact->notes()->create([
                        'body' => $faker->realText(rand(40, 500)),
                        'account_id' => $contact->account_id,
                    ]);

                    $contact->logEvent('note', $note->id, 'create');
                }
            }

            // activities
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 13); $j++) {
                    $activity = $contact->activities()->create([
                        'summary' => $faker->realText(rand(40, 100)),
                        'date_it_happened' => $faker->date($format = 'Y-m-d', $max = 'now'),
                        'activity_type_id' => rand(1, 13),
                        'description' => $faker->realText(rand(100, 1000)),
                        'account_id' => $contact->account_id,
                    ]);

                    $contact->logEvent('activity', $activity->id, 'create');
                }
            }

            // tasks
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 10); $j++) {
                    $task = $contact->tasks()->create([
                        'title' => $faker->realText(rand(40, 100)),
                        'description' => $faker->realText(rand(100, 1000)),
                        'completed' => (rand(1, 2) == 1 ? 0 : 1),
                        'completed_at' => (rand(1, 2) == 1 ? $faker->dateTimeThisCentury() : null),
                        'account_id' => $contact->account_id,
                    ]);

                    $contact->logEvent('task', $task->id, 'create');
                }
            }

            // debts
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 6); $j++) {
                    $debt = $contact->debts()->create([
                        'in_debt' => (rand(1, 2) == 1 ? 'yes' : 'no'),
                        'amount' => rand(321, 39391),
                        'reason' => $faker->realText(rand(100, 1000)),
                        'status' => 'inprogress',
                        'account_id' => $contact->account_id,
                    ]);

                    $contact->logEvent('debt', $debt->id, 'create');
                }
            }

            // gifts
            if (rand(1, 2) == 1) {
                for ($j = 0; $j < rand(1, 31); $j++) {
                    $gift = $contact->gifts()->create([

                        'name' => $faker->realText(rand(10, 100)),
                        'comment' => $faker->realText(rand(1000, 5000)),
                        'url' => $faker->url,
                        'value' => rand(12, 120),
                        'account_id' => $contact->account_id,
                        'is_an_idea' => 'true',
                        'has_been_offered' => 'false',
                    ]);

                    $contact->logEvent('gift', $gift->id, 'create');
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
