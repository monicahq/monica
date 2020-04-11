<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Helpers\DateHelper;
use App\Models\Contact\Tag;
use App\Models\Contact\Gift;
use App\Helpers\StringHelper;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Models\Contact\Reminder;
use App\Models\Settings\Currency;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactTest extends FeatureTestCase
{
    use DatabaseTransactions, WithFaker;

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
     * @return array
     */
    private function fetchUser()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        return [$user, $contact];
    }

    public function test_user_can_query_search_contacts()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $randomContact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $searchableFields = $randomContact->getSearchableFields();
        $keyword = $randomContact->first_name.' '.$randomContact->last_name;

        $queryString = StringHelper::buildQuery($searchableFields, $keyword);
        $records = Contact::whereRaw($queryString)->get();

        $this->assertGreaterThanOrEqual(1, count($records));
    }

    public function test_user_can_query_search_no_result()
    {
        $user = $this->signIn();

        $contacts = factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);

        $searchableFields = $contacts[0]->getSearchableFields();
        $keyword = 'no_result_with_this_keyword';

        $queryString = StringHelper::buildQuery($searchableFields, $keyword);
        $records = Contact::whereRaw($queryString)->get();

        $this->assertEquals(0, count($records));
    }

    public function test_user_can_search_one_contact_firstname()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $randomContact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $response = $this->post('/people/search', [
            'needle' => $randomContact->first_name,
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $randomContact->id,
            'complete_name' => $randomContact->first_name.' '.$randomContact->last_name,
        ]);
    }

    public function test_user_can_search_one_contact_lastname()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $randomContact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $response = $this->post('/people/search', [
            'needle' => $randomContact->last_name,
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $randomContact->id,
            'complete_name' => $randomContact->first_name.' '.$randomContact->last_name,
        ]);
    }

    public function test_user_can_search_one_contact_firstname_lastname()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $randomContact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $response = $this->post('/people/search', [
            'needle' => $randomContact->first_name.' '.$randomContact->last_name,
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $randomContact->id,
            'complete_name' => $randomContact->first_name.' '.$randomContact->last_name,
        ]);
    }

    public function test_user_can_search_one_contact_lastname_firstname()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $randomContact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $response = $this->post('/people/search', [
            'needle' => $randomContact->last_name.' '.$randomContact->first_name,
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $randomContact->id,
            'complete_name' => $randomContact->first_name.' '.$randomContact->last_name,
        ]);
    }

    public function test_user_can_search_one_contact_no_result()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->post('/people/search', [
            'needle' => 'no_result_with_this needle',
        ]);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'noResults' => 'No results found',
        ]);
    }

    public function test_user_can_list_one_contact_firstname()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $randomContact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $response = $this->get('/people/list?search='.$randomContact->first_name);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $randomContact->id,
            'complete_name' => $randomContact->first_name.' '.$randomContact->last_name,
        ]);
    }

    public function test_user_can_list_contacts_with_tag()
    {
        $user = $this->signIn();

        factory(Contact::class, 10)->state('named')->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::where('account_id', $user->account_id)
                            ->inRandomOrder()
                            ->first();

        $tag = factory(Tag::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contact->tags()->sync([
            $tag->id => [
                'account_id' => $user->account_id,
            ],
        ]);

        $response = $this->get('/people/list?tag1='.$tag->name_slug);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'id' => $contact->id,
            'complete_name' => $contact->first_name.' '.$contact->last_name,
        ]);
    }

    public function test_user_can_see_contacts()
    {
        [$user, $contact] = $this->fetchUser();
        $response = $this->get('/people');
        $response->assertSee('1 contact');
    }

    public function test_user_can_see_contacts_sorted_by_lastactivitydateNewtoOld()
    {
        $user = $this->signIn();

        $contacts = factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        foreach ($contacts as $contact) {
            factory(Activity::class)->create([
                'account_id' => $contact->account_id,
            ]);
        }

        $response = $this->get('/people/list?sort=lastactivitydateNewtoOld');

        $response->assertJsonFragment([
            'totalRecords' => 10,
        ]);
    }

    public function test_user_can_see_contacts_sorted_by_lastactivitydateOldtoNew()
    {
        $user = $this->signIn();

        $contacts = factory(Contact::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        foreach ($contacts as $contact) {
            factory(Activity::class)->create([
                'account_id' => $contact->account_id,
            ]);
        }

        $response = $this->get('/people/list?sort=lastactivitydateOldtoNew');

        $response->assertJsonFragment([
            'totalRecords' => 10,
        ]);
    }

    public function test_user_can_be_reminded_about_an_event_once()
    {
        [$user, $contact] = $this->fetchUser();

        $reminder = [
            'title' => $this->faker->sentence('5'),
            'initial_date' => DateHelper::getDate(DateHelper::parseDateTime($this->faker->dateTimeBetween('now', '+2 years'))),
            'frequency_type' => 'one_time',
            'description' => $this->faker->sentence(),
        ];

        $this->post(
            route('people.reminders.store', $contact),
            $reminder
        );

        $this->assertDatabaseHas(
            'reminders',
            array_merge($reminder, [
                'frequency_type' => 'one_time',
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
            ])
        );
    }

    public function test_user_can_add_a_task_to_a_contact()
    {
        [$user, $contact] = $this->fetchUser();

        $task = [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->sentence(3),
            'completed' => 0,
            'contact_id' => $contact->id,
        ];

        $this->post(
            '/tasks',
            $task
        );

        $this->assertDatabaseHas(
            'tasks',
            $task + [
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
            ]
        );
    }

    public function test_user_can_be_in_debt_to_a_contact()
    {
        [$user, $contact] = $this->fetchUser();

        $debt = [
            'in_debt' => 'yes',
            'amount' => $this->faker->numberBetween(1, 5000),
            'reason' => $this->faker->sentence(),
        ];

        $this->post(
            route('people.debts.store', $contact),
            $debt
        );

        $this->assertDatabaseHas('debts',
            $debt + [
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
            ]);
    }

    public function test_user_can_be_owed_debt_by_a_contact()
    {
        [$user, $contact] = $this->fetchUser();

        $debt = [
            'in_debt' => 'no',
            'amount' => $this->faker->numberBetween(1, 5000),
            'reason' => $this->faker->sentence(),
        ];

        $this->post(
            route('people.debts.store', $contact),
            $debt
        );

        $this->assertDatabaseHas('debts',
            $debt + [
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
            ]);
    }

    public function test_a_contact_edit_food_preferences()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID().'/food');

        $response->assertStatus(200);
        $response->assertSee('Indicate food preferences');
    }

    public function test_a_contact_can_have_food_preferences()
    {
        [$user, $contact] = $this->fetchUser();

        $food = ['food' => $this->faker->sentence()];

        $this->post('/people/'.$contact->hashID().'/food/save', $food);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'food_preferences' => $food['food'],
        ]);
    }

    public function test_a_contact_edit_work()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID().'/work/edit');

        $response->assertStatus(200);
        $response->assertSee("Update {$contact->first_name}’s job information");
    }

    public function test_a_contact_can_update_work()
    {
        [$user, $contact] = $this->fetchUser();

        $input = [
            'job' => $this->faker->sentence(),
            'company' => $this->faker->sentence(),
        ];

        $response = $this->post('/people/'.$contact->hashID().'/work/update', $input);
        $response->assertStatus(302);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'job' => $input['job'],
            'company' =>  $input['company'],
        ]);
    }

    public function test_a_contact_can_have_its_last_name_removed()
    {
        [$user, $contact] = $this->fetchUser();

        $data = [
            'firstname' => $contact->first_name,
            'lastname' => '',
            'gender' => $contact->gender_id,
            'birthdate' => 'unknown',
        ];

        $this->put('/people/'.$contact->hashID(), $data);

        $data['id'] = $contact->id;
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'last_name' => null,
        ]);
    }

    public function test_user_cant_add_new_contacts_if_limit_reached()
    {
        [$user, $contact] = $this->fetchUser();

        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        config(['monica.requires_subscription' => true]);

        $response = $this->get('/people/add');

        $response->assertRedirect('/settings/subscriptions');
    }

    public function test_user_can_add_new_contacts_when_instance_requires_no_subscription()
    {
        [$user, $contact] = $this->fetchUser();

        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        config(['monica.requires_subscription' => false]);

        $response = $this->get('/people/add');

        $response->assertStatus(200);
    }

    public function test_viewing_a_user_increments_the_number_of_views()
    {
        [$user, $contact] = $this->fetchUser();

        $this->assertDatabaseHas('contacts', [
            'number_of_views' => 0,
        ]);

        $response = $this->get('/people/'.$contact->hashID());
        $response = $this->get('/people/'.$contact->hashID());

        $this->assertDatabaseHas('contacts', [
            'number_of_views' => 2,
        ]);
    }

    public function test_vcard_download()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID().'/vcard');

        $response->assertOk();
        $response->assertHeader('Content-type', 'text/x-vcard; charset=UTF-8');
        $response->assertSee('FN:John Doe');
        $response->assertSee('N:Doe;John;;;');
    }

    public function test_edit_contact_has_specialdeceased()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID().'/edit');

        $response->assertSee('<form-specialdeceased
          :value="false"
          :date="\'\'"
          :reminder="false"
        >
        </form-specialdeceased>', false);
    }

    public function test_edit_contact_with_specialdeceased()
    {
        [$user, $contact] = $this->fetchUser();

        $reminder = factory(Reminder::class)->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $contact->is_dead = true;
        $contact->deceased_reminder_id = $reminder->id;
        $contact->save();

        $response = $this->get('/people/'.$contact->hashID().'/edit');

        $response->assertSee('<form-specialdeceased
          :value="true"
          :date="\''.$reminder->initial_date.'\'"
          :reminder="true"
        >
        </form-specialdeceased>', false);
    }

    public function test_edit_contact_put_deceased()
    {
        [$user, $contact] = $this->fetchUser();

        $data = [
            'firstname' => $contact->first_name,
            'lastname' => $contact->last_name,
            'gender' => $contact->gender_id,
            'birthdate' => 'unknown',
            'is_deceased' => 'true',
            'is_deceased_date_known' => 'true',
            'deceased_date' => '2012-06-22',
        ];

        $this->put('/people/'.$contact->hashID(), $data);

        $data['id'] = $contact->id;
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'is_dead' => true,
        ]);

        $contact->refresh();
        $this->assertDatabaseHas('special_dates', [
            'id' => $contact->deceased_special_date_id,
            'date' => '2012-06-22',
        ]);
    }

    public function test_edit_contact_put_deceased_dont_stay_in_touch()
    {
        [$user, $contact] = $this->fetchUser();

        $data = [
            'firstname' => $contact->first_name,
            'lastname' => $contact->last_name,
            'gender' => $contact->gender_id,
            'birthdate' => 'unknown',
            'is_deceased' => 'true',
            'is_deceased_date_known' => 'true',
            'deceased_date' => '2012-06-22',
            'stay_in_touch_frequency' => 11,
            'stay_in_touch_trigger_date' => '2012-06-22',
        ];

        $this->put('/people/'.$contact->hashID(), $data);

        $contact->updateStayInTouchFrequency(0);
        $contact->setStayInTouchTriggerDate(0);

        $data['id'] = $contact->id;
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'is_dead' => true,
            'stay_in_touch_frequency' => null,
            'stay_in_touch_trigger_date' => null,
        ]);
    }

    public function test_edit_contact_put_deceased_with_reminder()
    {
        [$user, $contact] = $this->fetchUser();

        $data = [
            'firstname' => $contact->first_name,
            'lastname' => $contact->last_name,
            'gender' => $contact->gender_id,
            'birthdate' => 'unknown',
            'is_deceased' => 'true',
            'is_deceased_date_known' => 'true',
            'deceased_date' => '2012-06-22',
            'add_reminder_deceased' => 'true',
        ];

        $this->put('/people/'.$contact->hashID(), $data);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'is_dead' => true,
        ]);

        $contact->refresh();
        $this->assertDatabaseHas('special_dates', [
            'id' => $contact->deceased_special_date_id,
            'date' => '2012-06-22',
        ]);
        $this->assertDatabaseHas('reminders', [
            'id' => $contact->deceased_reminder_id,
            'contact_id' => $contact->id,
            'initial_date' => '2012-06-22',
        ]);
    }

    public function test_it_create_a_contact()
    {
        $user = $this->signIn();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Mike',
            'gender' => $gender->id,
        ];

        $response = $this->post('/people', $data);

        $response->assertStatus(302);

        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'Mike',
            'gender_id' => $gender->id,
        ]);
    }

    /** @test */
    public function it_gets_the_value()
    {
        $user = $this->signin();
        $currency = factory(Currency::class)->create([
            'iso' => 'USD',
            'symbol' => '$',
        ]);
        $user->currency()->associate($currency);
        $user->save();

        $gift = factory(Gift::class)->make();
        $gift->value = '100';

        $this->assertEquals(
            '$100.00',
            $gift->amount
        );
    }
}
