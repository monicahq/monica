<?php

use App\Account;
use App\Contact;
use GuzzleHttp\Client;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class FakeContentTableSeeder extends Seeder
{
    private $numberOfContacts;
    private $contact;
    private $faker;
    private $account;

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
        $account->populateDefaultGendersTable();

        // populate user table
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountID,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
            'timezone' => config('app.timezone'),
        ]);

        $this->account = $account;

        $this->faker = Faker::create();

        // create a random number of contacts
        $this->numberOfContacts = rand(60, 100);
        echo 'Generating '.$this->numberOfContacts.' fake contacts'.PHP_EOL;

        $output = new ConsoleOutput();
        $progress = new ProgressBar($output, $this->numberOfContacts);
        $progress->start();

        // fetching avatars
        $client = new Client();
        $res = $client->request('GET', 'https://randomuser.me/api/?results='.$this->numberOfContacts.'&inc=picture');
        $arrayPictures = json_decode($res->getBody());

        for ($i = 0; $i < $this->numberOfContacts; $i++) {
            $timezone = config('app.timezone');
            $gender = (rand(1, 2) == 1) ? 'male' : 'female';

            $this->contact = new Contact;
            $this->contact->account_id = $accountID;
            $this->contact->gender_id = $this->getRandomGender()->id;
            $this->contact->first_name = $this->faker->firstName($gender);
            $this->contact->last_name = (rand(1, 2) == 1) ? $this->faker->lastName : null;
            $this->contact->has_avatar = false;
            $this->contact->save();

            // set an external avatar
            if (rand(1, 2) == 1) {
                $this->contact->has_avatar = true;
                $this->contact->avatar_location = 'external';
                $this->contact->avatar_external_url = $arrayPictures->results[$i]->picture->large;
                $this->contact->save();
            }

            $this->contact->setAvatarColor();

            $this->populateFoodPreferencies();
            $this->populateDeceasedDate();
            $this->populateBirthday();
            $this->populateFirstMetInformation();
            $this->populateKids();
            $this->populatePartners();
            $this->populateNotes();
            $this->populateActivities();
            $this->populateTasks();
            $this->populateDebts();
            $this->populateCalls();
            $this->populateGifts();
            $this->populateAddresses();
            $this->populateContactFields();
            $this->populatePets();
            $this->changeUpdatedAt();

            $progress->advance();
        }

        $this->populateDayRatings();
        $this->populateEntries();

        $progress->finish();

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

    public function populateFoodPreferencies()
    {
        // add food preferencies
        if (rand(1, 2) == 1) {
            $this->contact->food_preferencies = $this->faker->realText();
            $this->contact->save();
        }
    }

    public function populateDeceasedDate()
    {
        // deceased?
        if (rand(1, 7) == 1) {
            $this->contact->is_dead = true;

            if (rand(1, 3) == 1) {
                $deceasedDate = $this->faker->dateTimeThisCentury();

                if (rand(1, 2) == 1) {
                    // add a date where we don't know the year
                    $specialDate = $this->contact->setSpecialDate('deceased_date', 0, $deceasedDate->format('m'), $deceasedDate->format('d'));
                } else {
                    // add a date where we know the year
                    $specialDate = $this->contact->setSpecialDate('deceased_date', $deceasedDate->format('Y'), $deceasedDate->format('m'), $deceasedDate->format('d'));
                }
                $newReminder = $specialDate->setReminder('year', 1, trans('people.deceased_reminder_title', ['name' => $this->contact->first_name]));
            }

            $this->contact->save();
        }
    }

    public function populateBirthday()
    {
        if (rand(1, 2) == 1) {
            $birthdate = $this->faker->dateTimeThisCentury();

            if (rand(1, 2) == 1) {
                if (rand(1, 2) == 1) {
                    // add a date where we don't know the year
                    $specialDate = $this->contact->setSpecialDate('birthdate', 0, $birthdate->format('m'), $birthdate->format('d'));
                } else {
                    // add a date where we know the year
                    $specialDate = $this->contact->setSpecialDate('birthdate', $birthdate->format('Y'), $birthdate->format('m'), $birthdate->format('d'));
                }

                $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $this->contact->first_name]));
            } else {
                // add a birthdate based on an approximate age
                $specialDate = $this->contact->setSpecialDateFromAge('birthdate', rand(10, 100));
            }
        }
    }

    public function populateFirstMetInformation()
    {
        if (rand(1, 2) == 1) {
            $this->contact->first_met_where = $this->faker->realText(20);
        }

        if (rand(1, 2) == 1) {
            $this->contact->first_met_additional_info = $this->faker->realText(20);
        }

        if (rand(1, 2) == 1) {
            $firstMetDate = $this->faker->dateTimeThisCentury();

            if (rand(1, 2) == 1) {
                // add a date where we don't know the year
                $specialDate = $this->contact->setSpecialDate('first_met', 0, $firstMetDate->format('m'), $firstMetDate->format('d'));
            } else {
                // add a date where we know the year
                $specialDate = $this->contact->setSpecialDate('first_met', $firstMetDate->format('Y'), $firstMetDate->format('m'), $firstMetDate->format('d'));
            }
            $newReminder = $specialDate->setReminder('year', 1, trans('people.introductions_reminder_title', ['name' => $this->contact->first_name]));
        }

        if (rand(1, 2) == 1) {
            do {
                $rand = rand(1, $this->numberOfContacts);
            } while (in_array($rand, [$this->contact->id]));

            $this->contact->first_met_through_contact_id = $rand;
        }

        $this->contact->save();
    }

    public function populateKids()
    {
        if (rand(1, 2) == 1) {
            foreach (range(1, rand(2, 6)) as $index) {
                $gender = (rand(1, 2) == 1) ? 'male' : 'female';

                $kid = new Contact;
                $kid->account_id = $this->contact->account_id;
                $kid->gender_id = $this->getRandomGender()->id;
                $kid->first_name = $this->faker->firstName($gender);
                $kid->last_name = (rand(1, 2) == 1) ? $this->faker->lastName($gender) : null;
                $kid->save();

                // is real contact?
                if (rand(1, 2) == 1) {
                    $kid->is_partial = true;
                    $kid->isTheOffspringOf($this->contact);
                } else {
                    $kid->is_partial = false;
                    $kid->isTheOffspringOf($this->contact, true);
                }
                $kid->save();

                $kid->setAvatarColor();

                // birthdate
                $kidBirthDate = $this->faker->dateTimeThisCentury();
                if (rand(1, 2) == 1) {
                    // add a date where we don't know the year
                    $specialDate = $kid->setSpecialDate('birthdate', 0, $kidBirthDate->format('m'), $kidBirthDate->format('d'));
                } else {
                    // add a date where we know the year
                    $specialDate = $kid->setSpecialDate('birthdate', $kidBirthDate->format('Y'), $kidBirthDate->format('m'), $kidBirthDate->format('d'));
                }
                $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $kid->first_name]));
            }
        }
    }

    public function populatePartners()
    {
        if (rand(1, 2) == 1) {
            foreach (range(1, rand(2, 6)) as $index) {
                $gender = (rand(1, 2) == 1) ? 'male' : 'female';

                $partner = new Contact;
                $partner->account_id = $this->contact->account_id;
                $partner->gender_id = $this->getRandomGender()->id;
                $partner->first_name = $this->faker->firstName($gender);
                $partner->last_name = (rand(1, 2) == 1) ? $this->faker->lastName($gender) : null;
                $partner->save();

                // is real contact?
                if (rand(1, 2) == 1) {
                    $partner->is_partial = true;
                    $this->contact->setRelationshipWith($partner);
                } else {
                    $partner->is_partial = false;
                    $this->contact->setRelationshipWith($partner, true);
                }
                $partner->save();

                $partner->setAvatarColor();

                // birthdate
                $partnerBirthDate = $this->faker->dateTimeThisCentury();
                if (rand(1, 2) == 1) {
                    // add a date where we don't know the year
                    $specialDate = $partner->setSpecialDate('birthdate', 0, $partnerBirthDate->format('m'), $partnerBirthDate->format('d'));
                } else {
                    // add a date where we know the year
                    $specialDate = $partner->setSpecialDate('birthdate', $partnerBirthDate->format('Y'), $partnerBirthDate->format('m'), $partnerBirthDate->format('d'));
                }
                $newReminder = $specialDate->setReminder('year', 1, trans('people.people_add_birthday_reminder', ['name' => $partner->first_name]));
            }
        }
    }

    public function populateNotes()
    {
        if (rand(1, 2) == 1) {
            for ($j = 0; $j < rand(1, 13); $j++) {
                $note = $this->contact->notes()->create([
                    'body' => $this->faker->realText(rand(40, 500)),
                    'account_id' => $this->contact->account_id,
                    'is_favorited' => (rand(1, 3) == 1 ? true : false),
                    'favorited_at' => $this->faker->dateTimeThisCentury(),
                ]);

                $this->contact->logEvent('note', $note->id, 'create');
            }
        }
    }

    public function populateActivities()
    {
        if (rand(1, 2) == 1) {
            for ($j = 0; $j < rand(1, 13); $j++) {
                $date = $this->faker->dateTimeThisYear($max = 'now')->format('Y-m-d');

                $activity = $this->contact->activities()->create([
                    'summary' => $this->faker->realText(rand(40, 100)),
                    'date_it_happened' => $date,
                    'activity_type_id' => rand(1, 13),
                    'description' => (rand(1, 2) == 1 ? $this->faker->realText(rand(100, 1000)) : null),
                    'account_id' => $this->contact->account_id,
                ], ['account_id' => $this->contact->account_id]);

                $entry = DB::table('journal_entries')->insertGetId([
                    'account_id' => $this->account->id,
                    'date' => $date,
                    'journalable_id' => $activity->id,
                    'journalable_type' => 'App\Activity',
                ]);

                $this->contact->logEvent('activity', $activity->id, 'create');
            }
        }
    }

    public function populateTasks()
    {
        if (rand(1, 2) == 1) {
            for ($j = 0; $j < rand(1, 10); $j++) {
                $task = $this->contact->tasks()->create([
                    'title' => $this->faker->realText(rand(40, 100)),
                    'description' => $this->faker->realText(rand(100, 1000)),
                    'completed' => (rand(1, 2) == 1 ? 0 : 1),
                    'completed_at' => (rand(1, 2) == 1 ? $this->faker->dateTimeThisCentury() : null),
                    'account_id' => $this->contact->account_id,
                ]);

                $this->contact->logEvent('task', $task->id, 'create');
            }
        }
    }

    public function populateDebts()
    {
        if (rand(1, 2) == 1) {
            for ($j = 0; $j < rand(1, 6); $j++) {
                $debt = $this->contact->debts()->create([
                    'in_debt' => (rand(1, 2) == 1 ? 'yes' : 'no'),
                    'amount' => rand(321, 39391),
                    'reason' => $this->faker->realText(rand(100, 1000)),
                    'status' => 'inprogress',
                    'account_id' => $this->contact->account_id,
                ]);

                $this->contact->logEvent('debt', $debt->id, 'create');
            }
        }
    }

    public function populateGifts()
    {
        if (rand(1, 2) == 1) {
            for ($j = 0; $j < rand(1, 31); $j++) {
                $gift = $this->contact->gifts()->create([

                    'name' => $this->faker->realText(rand(10, 100)),
                    'comment' => $this->faker->realText(rand(1000, 5000)),
                    'url' => $this->faker->url,
                    'value' => rand(12, 120),
                    'account_id' => $this->contact->account_id,
                    'is_an_idea' => true,
                    'has_been_offered' => false,
                ]);

                $this->contact->logEvent('gift', $gift->id, 'create');
            }
        }
    }

    public function populateAddresses()
    {
        if (rand(1, 3) == 1) {
            $address = $this->contact->addresses()->create([
                'account_id' => $this->contact->account_id,
                'country_id' => rand(1, 242),
                'name' => $this->faker->word,
                'street' => (rand(1, 3) == 1) ? $this->faker->streetAddress : null,
                'city' => (rand(1, 3) == 1) ? $this->faker->city : null,
                'province' => (rand(1, 3) == 1) ? $this->faker->state : null,
                'postal_code' => (rand(1, 3) == 1) ? $this->faker->postcode : null,
            ]);
        }
    }

    public function populateContactFields()
    {
        if (rand(1, 3) == 1) {
            for ($j = 0; $j < rand(1, 4); $j++) {
                $contactField = $this->contact->contactFields()->create([
                    'contact_field_type_id' => rand(1, 6),
                    'data' => $this->faker->url,
                    'account_id' => $this->contact->account->id,
                ]);
            }
        }
    }

    public function populateEntries()
    {
        for ($j = 0; $j < rand(10, 100); $j++) {
            $date = $this->faker->dateTimeThisYear();

            $entryId = DB::table('entries')->insertGetId([
                'account_id' => $this->account->id,
                'title' => $this->faker->realText(rand(12, 20)),
                'post' => $this->faker->realText(rand(400, 500)),
                'created_at' => $date,
            ]);

            $journalEntry = DB::table('journal_entries')->insertGetId([
                'account_id' => $this->account->id,
                'date' => $date,
                'journalable_id' => $entryId,
                'journalable_type' => 'App\Entry',
                'created_at' => \Carbon\Carbon::now(),
            ]);
        }
    }

    public function populatePets()
    {
        if (rand(1, 3) == 1) {
            for ($j = 0; $j < rand(1, 3); $j++) {
                $date = $this->faker->dateTimeThisYear();

                $petId = DB::table('pets')->insertGetId([
                    'account_id' => $this->account->id,
                    'contact_id' => $this->contact->id,
                    'pet_category_id' => rand(1, 11),
                    'name' => (rand(1, 3) == 1) ? $this->faker->firstName : null,
                    'created_at' => $date,
                ]);
            }
        }
    }

    public function populateDayRatings()
    {
        for ($j = 0; $j < rand(10, 100); $j++) {
            $date = $this->faker->dateTimeThisYear();

            $dayId = DB::table('days')->insertGetId([
                'account_id' => $this->account->id,
                'rate' => rand(1, 3),
                'date' => $date,
                'created_at' => $date,
            ]);

            $journalEntry = DB::table('journal_entries')->insertGetId([
                'account_id' => $this->account->id,
                'date' => $date,
                'journalable_id' => $dayId,
                'journalable_type' => 'App\Day',
                'created_at' => \Carbon\Carbon::now(),
            ]);
        }
    }

    public function changeUpdatedAt()
    {
        $this->contact->last_consulted_at = $this->faker->dateTimeThisYear();
        $this->contact->save();
    }

    public function populateCalls()
    {
        if (rand(1, 3) == 1) {
            $calls = $this->contact->calls()->create([
                'account_id' => $this->contact->account_id,
                'called_at' => $this->faker->dateTimeThisYear(),
            ]);
        }
    }

    public function getRandomGender()
    {
        $genders = $this->account->genders;

        return $genders->random();
    }
}
