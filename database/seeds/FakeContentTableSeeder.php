<?php

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Database\Seeder;
use App\Helpers\CountriesHelper;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\LifeEventType;
use App\Models\Contact\ContactFieldType;
use App\Services\Contact\Tag\AssociateTag;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Contact\Address\CreateAddress;
use App\Services\Contact\Contact\CreateContact;
use App\Services\Contact\Reminder\CreateReminder;
use Symfony\Component\Console\Helper\ProgressBar;
use App\Services\Contact\LifeEvent\CreateLifeEvent;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Services\Contact\Conversation\CreateConversation;
use App\Services\Contact\Contact\UpdateBirthdayInformation;
use App\Services\Contact\Contact\UpdateDeceasedInformation;
use App\Services\Contact\Conversation\AddMessageToConversation;

class FakeContentTableSeeder extends Seeder
{
    use WithFaker;

    private $numberOfContacts;
    private $contact;
    private $account;
    private $countries = null;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->setUpFaker();

        // Get or create the first account
        if (User::where('email', 'admin@admin.com')->exists()) {
            $userId = User::where('email', 'admin@admin.com')->value('id');
            $this->account = Account::where('id', $userId)->first();
        } else {
            $this->account = Account::createDefault('John', 'Doe', 'admin@admin.com', 'admin');

            // set default admin account to confirmed
            $adminUser = $this->account->users()->first();
            $this->confirmUser($adminUser);
        }

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
            $gender = (rand(1, 2) == 1) ? 'male' : 'female';

            $this->contact = app(CreateContact::class)->execute([
                'account_id' => $this->account->id,
                'first_name' => $this->faker->firstName($gender),
                'last_name' => (rand(1, 2) == 1) ? $this->faker->lastName : null,
                'nickname' => (rand(1, 2) == 1) ? $this->faker->name : null,
                'gender_id' => $this->getRandomGender()->id,
                'is_partial' => false,
                'is_birthdate_known' => false,
                'is_deceased' => false,
                'is_deceased_date_known' => false,
            ]);

            $this->contact->setAvatarColor();
            $this->contact->save();

            // set an external avatar
            if (rand(1, 2) == 1) {
                $this->contact->has_avatar = true;
                $this->contact->avatar_location = 'external';
                $this->contact->avatar_external_url = $arrayPictures->results[$i]->picture->large;
                $this->contact->save();
            }

            $this->populateTags();
            $this->populateFoodPreferences();
            $this->populateDeceasedDate();
            $this->populateBirthday();
            $this->populateFirstMetInformation();
            $this->populateRelationships();
            $this->populateNotes();
            $this->populateActivities();
            $this->populateTasks();
            $this->populateDebts();
            $this->populateCalls();
            $this->populateConversations();
            $this->populateLifeEvents();
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
        if (! User::where('email', 'blank@blank.com')->exists()) {
            $blankAccount = Account::createDefault('Blank', 'State', 'blank@blank.com', 'blank');
            $blankUser = $blankAccount->users()->first();
            $this->confirmUser($blankUser);
        }
    }

    public function populateTags()
    {
        if (rand(1, 2) == 1) {
            $i = 0;
            do {
                app(AssociateTag::class)->execute([
                    'account_id' => $this->contact->account->id,
                    'contact_id' => $this->contact->id,
                    'name' => $this->faker->word,
                ]);
                $i++;
            } while ($i < 10);
        }
    }

    public function populateFoodPreferences()
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
            $birthdate = $this->faker->dateTimeThisCentury();

            app(UpdateDeceasedInformation::class)->execute([
                'account_id' => $this->contact->account_id,
                'contact_id' => $this->contact->id,
                'is_deceased' => rand(1, 2) == 1,
                'is_date_known' => rand(1, 2) == 1,
                'day' => (int) $birthdate->format('d'),
                'month' => (int) $birthdate->format('m'),
                'year' => (int) $birthdate->format('Y'),
                'add_reminder' => rand(1, 2) == 1,
            ]);
        }
    }

    public function populateBirthday()
    {
        if (rand(1, 2) == 1) {
            $birthdate = $this->faker->dateTimeThisCentury();

            app(UpdateBirthdayInformation::class)->execute([
                'account_id' => $this->contact->account_id,
                'contact_id' => $this->contact->id,
                'is_date_known' => rand(1, 2) == 1,
                'day' => (int) $birthdate->format('d'),
                'month' => (int) $birthdate->format('m'),
                'year' => (int) $birthdate->format('Y'),
                'is_age_based' => rand(1, 2) == 1,
                'age' => rand(1, 99),
                'add_reminder' => rand(1, 2) == 1,
            ]);
        }
    }

    public function populateFirstMetInformation()
    {
        if (rand(1, 2) == 1) {
            $this->contact->first_met_where = $this->faker->realText(20);
        }

        if (rand(1, 2) == 1) {
            $this->contact->first_met_additional_info = $this->faker->realText(20);
            $firstMetDate = $this->faker->dateTimeThisCentury();

            if (rand(1, 2) == 1) {
                // add a date where we don't know the year
                $specialDate = $this->contact->setSpecialDate('first_met', 0, $firstMetDate->format('m'), $firstMetDate->format('d'));
            } else {
                // add a date where we know the year
                $specialDate = $this->contact->setSpecialDate('first_met', $firstMetDate->format('Y'), $firstMetDate->format('m'), $firstMetDate->format('d'));
            }
            app(CreateReminder::class)->execute([
                'account_id' => $this->contact->account_id,
                'contact_id' => $this->contact->id,
                'initial_date' => $specialDate->date->toDateString(),
                'frequency_type' => 'year',
                'frequency_number' => 1,
                'title' => trans(
                    'people.introductions_reminder_title',
                    ['name' => $this->contact->first_name]
                ),
            ]);
        }

        if (rand(1, 2) == 1) {
            do {
                $rand = rand(1, $this->numberOfContacts);
            } while (in_array($rand, [$this->contact->id]));

            $this->contact->first_met_through_contact_id = $rand;
        }

        $this->contact->save();
    }

    public function populateRelationships()
    {
        if (rand(1, 2) == 1) {
            foreach (range(1, rand(2, 6)) as $index) {
                $gender = (rand(1, 2) == 1) ? 'male' : 'female';

                $relatedContact = app(CreateContact::class)->execute([
                    'account_id' => $this->contact->account_id,
                    'first_name' => $this->faker->firstName($gender),
                    'last_name' => (rand(1, 2) == 1) ? $this->faker->lastName : null,
                    'nickname' => (rand(1, 2) == 1) ? $this->faker->name : null,
                    'gender_id' => $this->getRandomGender()->id,
                    'is_partial' => (rand(1, 2) == 1) ? false : true,
                    'is_birthdate_known' => false,
                    'is_deceased' => false,
                    'is_deceased_date_known' => false,
                ]);

                $relatedContact->setAvatarColor();
                $relatedContact->save();

                // birthdate
                $relatedContactBirthDate = $this->faker->dateTimeThisCentury();
                app(UpdateBirthdayInformation::class)->execute([
                    'account_id' => $this->contact->account_id,
                    'contact_id' => $relatedContact->id,
                    'is_date_known' => rand(1, 2) == 1,
                    'day' => (int) $relatedContactBirthDate->format('d'),
                    'month' => (int) $relatedContactBirthDate->format('m'),
                    'year' => (int) $relatedContactBirthDate->format('Y'),
                    'is_age_based' => rand(1, 2) == 1,
                    'age' => rand(1, 99),
                    'add_reminder' => rand(1, 2) == 1,
                ]);

                // set relationship
                $relationshipId = $this->contact->account->relationshipTypes->random()->id;
                $this->contact->setRelationship($relatedContact, $relationshipId);
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
                    'is_favorited' => rand(1, 3) == 1,
                    'favorited_at' => $this->faker->dateTimeThisCentury(),
                ]);
            }
        }
    }

    public function populateActivities()
    {
        if (rand(1, 2) == 1) {
            for ($j = 0; $j < rand(1, 13); $j++) {
                $date = Carbon::instance($this->faker->dateTimeThisYear($max = 'now'))->toDateString();

                $activity = $this->contact->activities()->create([
                    'summary' => $this->faker->realText(rand(40, 100)),
                    'date_it_happened' => $date,
                    'activity_type_id' => rand(1, 13),
                    'description' => (rand(1, 2) == 1 ? $this->faker->realText(rand(100, 1000)) : null),
                    'account_id' => $this->contact->account_id,
                ], ['account_id' => $this->contact->account_id]);

                DB::table('journal_entries')->insertGetId([
                    'account_id' => $this->account->id,
                    'date' => $date,
                    'journalable_id' => $activity->id,
                    'journalable_type' => 'App\Models\Account\Activity',
                ]);
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
            }
        }
    }

    public function populateAddresses()
    {
        if (rand(1, 3) == 1) {
            $request = [
                'account_id' => $this->contact->account_id,
                'contact_id' => $this->contact->id,
                'country' => $this->getRandomCountry(),
                'name' => $this->faker->word,
                'street' => (rand(1, 3) == 1) ? $this->faker->streetAddress : null,
                'city' => (rand(1, 3) == 1) ? $this->faker->city : null,
                'province' => (rand(1, 3) == 1) ? $this->faker->state : null,
                'postal_code' => (rand(1, 3) == 1) ? $this->faker->postcode : null,
            ];

            app(CreateAddress::class)->execute($request);
        }
    }

    private function getRandomCountry()
    {
        if ($this->countries == null) {
            $this->countries = CountriesHelper::getAll();
        }

        return $this->countries->random()->id;
    }

    public function populateContactFields()
    {
        if (rand(1, 3) == 1) {

            // Fetch number of types
            $numberOfTypes = ContactFieldType::where('account_id', $this->account->id)->count();

            for ($j = 0; $j < rand(1, $numberOfTypes); $j++) {
                // Retrieve random ContactFieldType
                $contactFieldType = ContactFieldType::where('account_id', $this->account->id)->orderBy(DB::raw('RAND()'))->firstOrFail();

                // Fake data according to type
                $data = null;
                switch ($contactFieldType->name) {
                    case 'Email':
                        $data = $this->faker->email;
                    break;
                    case 'Phone':
                        $data = $this->faker->phoneNumber;
                    break;
                    case 'Facebook':
                        $data = 'https://facebook.com/'.$this->faker->userName;
                    break;
                    case 'Twitter':
                        $data = 'https://twitter.com/'.$this->faker->userName;
                    break;
                    case 'Whatsapp':
                        $data = $this->faker->phoneNumber;
                    break;
                    case 'Telegram':
                        $data = $this->faker->phoneNumber;
                    break;
                    default:
                        $data = $this->faker->url;
                    break;
                }

                $this->contact->contactFields()->create([
                    'contact_field_type_id' => $contactFieldType->id,
                    'data' => $data,
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

            DB::table('journal_entries')->insertGetId([
                'account_id' => $this->account->id,
                'date' => $date,
                'journalable_id' => $entryId,
                'journalable_type' => 'App\Models\Journal\Entry',
                'created_at' => now(),
            ]);
        }
    }

    public function populatePets()
    {
        if (rand(1, 3) == 1) {
            for ($j = 0; $j < rand(1, 3); $j++) {
                $date = $this->faker->dateTimeThisYear();

                DB::table('pets')->insertGetId([
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

            DB::table('journal_entries')->insertGetId([
                'account_id' => $this->account->id,
                'date' => $date,
                'journalable_id' => $dayId,
                'journalable_type' => 'App\Models\Journal\Day',
                'created_at' => now(),
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
            $this->contact->calls()->create([
                'account_id' => $this->contact->account_id,
                'called_at' => $this->faker->dateTimeThisYear(),
            ]);
        }
    }

    public function populateConversations()
    {
        if (rand(1, 1) == 1) {
            for ($j = 0; $j < rand(1, 20); $j++) {
                $contactFieldType = ContactFieldType::where('account_id', $this->account->id)->orderBy(DB::raw('RAND()'))->firstOrFail();

                $conversation = app(CreateConversation::class)->execute([
                    'happened_at' => $this->faker->dateTimeThisCentury(),
                    'contact_id' => $this->contact->id,
                    'contact_field_type_id' => $contactFieldType->id,
                    'account_id' => $this->contact->account->id,
                ]);

                for ($k = 0; $k < rand(1, 20); $k++) {
                    app(AddMessageToConversation::class)->execute([
                        'account_id' => $this->contact->account->id,
                        'contact_id' => $this->contact->id,
                        'conversation_id' => $conversation->id,
                        'written_at' => $this->faker->dateTimeThisCentury(),
                        'written_by_me' => (rand(1, 2) == 1),
                        'content' => $this->faker->realText(),
                    ]);
                }
            }
        }
    }

    public function populateLifeEvents()
    {
        if (rand(1, 1) == 1) {
            for ($j = 0; $j < rand(1, 20); $j++) {
                $lifeEventType = LifeEventType::where('account_id', $this->account->id)->orderBy(DB::raw('RAND()'))->firstOrFail();

                app(CreateLifeEvent::class)->execute([
                    'account_id' => $this->contact->account->id,
                    'contact_id' => $this->contact->id,
                    'life_event_type_id' => $lifeEventType->id,
                    'happened_at' => $this->faker->dateTimeThisCentury(),
                    'name' => $this->faker->realText(),
                    'note' => $this->faker->realText(),
                    'has_reminder' => false,
                    'happened_at_month_unknown' => false,
                    'happened_at_day_unknown' => false,
                ]);
            }
        }
    }

    public function getRandomGender()
    {
        return $this->account->genders->random();
    }

    public function confirmUser($user)
    {
        $user->markEmailAsVerified();
    }
}
