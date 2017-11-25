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
        // truncate all the tables
        DB::table('accounts')->delete();
        DB::table('users')->delete();
        DB::table('contacts')->delete();
        DB::table('reminders')->delete();
        DB::table('tasks')->delete();
        DB::table('notes')->delete();
        DB::table('activities')->delete();

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
                'last_name' => (rand(1, 2) == 1) ? $faker->lastName : null,
            ]);

            $contact = Contact::find($contactID);
            $contact->setAvatarColor();

            // add food preferencies
            if (rand(1, 2) == 1) {
                $contact->food_preferencies = $faker->realText();
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
                        'is_birthdate_approximate' => $birthdate_approximate,
                        'birthdate' => $birthdate_approximate !== 'unknown' ? $birthdate : null,
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
                for ($j = 0; $j < rand(1, 6); $j++) {
                    $task = $contact->tasks()->create([
                        'title' => $faker->realText(rand(40, 100)),
                        'description' => $faker->realText(rand(100, 1000)),
                        'status' => (rand(1, 2) == 1 ? 'inprogress' : 'completed'),
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
