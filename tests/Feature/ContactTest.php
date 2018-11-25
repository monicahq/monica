<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Gift;
use App\Models\Contact\Contact;
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

    public function test_user_can_see_contacts()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people');

        $response->assertSee(
            $contact->name
        );
    }

    public function test_user_can_be_reminded_about_an_event_once()
    {
        list($user, $contact) = $this->fetchUser();

        $reminder = [
            'title' => $this->faker->sentence('5'),
            'next_expected_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d H:i:s'),
            'frequency_type' => 'once',
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
        list($user, $contact) = $this->fetchUser();

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

    public function test_user_can_add_a_gift_idea_to_a_contact()
    {
        list($user, $contact) = $this->fetchUser();

        $gift = [
            'offered' => 'idea',
            'name' => $this->faker->word,
            'url' => $this->faker->url,
            'value' => $this->faker->numberBetween(1, 2000),
            'comment' => $this->faker->sentence(),
        ];

        $this->post(
            '/people/'.$contact->hashID().'/gifts',
            $gift
        );

        array_shift($gift);

        $this->assertDatabaseHas(
            'gifts',
            $gift + [
                'is_an_idea' => true,
                'has_been_offered' => false,
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
            ]
        );
    }

    public function test_user_can_add_a_gift_idea_with_recipient()
    {
        list($user, $contact) = $this->fetchUser();

        $otherContact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $gift = [
            'offered' => 'idea',
            'name' => $this->faker->word,
            'url' => $this->faker->url,
            'value' => $this->faker->numberBetween(1, 2000),
            'comment' => $this->faker->sentence(),
            'has_recipient' => true,
            'recipient' => $otherContact->id,
        ];

        $this->post(
            '/people/'.$contact->hashID().'/gifts',
            $gift
        );

        $gift = array_except($gift, ['offered', 'has_recipient', 'recipient']);

        $this->assertDatabaseHas(
            'gifts',
            $gift + [
                'is_an_idea' => true,
                'has_been_offered' => false,
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
                'is_for' => $otherContact->id,
            ]
        );
    }

    public function test_user_can_add_a_gift_idea_with_bad_recipient()
    {
        list($user, $contact) = $this->fetchUser();

        $gift = [
            'offered' => 'idea',
            'name' => $this->faker->word,
            'url' => $this->faker->url,
            'value' => $this->faker->numberBetween(1, 2000),
            'comment' => $this->faker->sentence(),
            'has_recipient' => true,
            'recipient' => 0,
        ];

        $this->post(
            '/people/'.$contact->hashID().'/gifts',
            $gift
        );

        $gift = array_except($gift, ['offered', 'has_recipient', 'recipient']);

        $this->assertDatabaseHas(
            'gifts',
            $gift + [
                'is_an_idea' => true,
                'has_been_offered' => false,
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
                'is_for' => null,
            ]
        );
    }

    public function test_user_can_edit_a_gift_()
    {
        list($user, $contact) = $this->fetchUser();

        $oldGift = factory(Gift::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
        ]);

        $gift = [
            'offered' => 'idea',
            'name' => $this->faker->word,
            'url' => $this->faker->url,
            'value' => $this->faker->numberBetween(1, 2000),
            'comment' => $this->faker->sentence(),
        ];

        $this->put(
            '/people/'.$contact->hashID().'/gifts/'.$oldGift->id,
            $gift
        );

        array_shift($gift);

        $this->assertDatabaseHas(
            'gifts',
            $gift + [
                'is_an_idea' => true,
                'has_been_offered' => false,
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
            ]
        );
    }

    public function test_user_can_add_recipient_to_a_gift()
    {
        list($user, $contact) = $this->fetchUser();

        $oldGift = factory(Gift::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
        ]);

        $otherContact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $gift = [
            'offered' => 'idea',
            'name' => $this->faker->word,
            'url' => $this->faker->url,
            'value' => $this->faker->numberBetween(1, 2000),
            'comment' => $this->faker->sentence(),
            'has_recipient' => true,
            'recipient' => $otherContact->id,
        ];

        $this->put(
            '/people/'.$contact->hashID().'/gifts/'.$oldGift->id,
            $gift
        );

        $gift = array_except($gift, ['offered', 'has_recipient', 'recipient']);

        $this->assertDatabaseHas(
            'gifts',
            $gift + [
                'is_an_idea' => true,
                'has_been_offered' => false,
                'contact_id' => $contact->id,
                'account_id' => $user->account_id,
                'is_for' => $otherContact->id,
            ]
        );

        $newGift = Gift::find($oldGift->id);
        $this->assertEquals($otherContact->first_name, $newGift->recipient_name);
    }

    public function test_user_can_be_in_debt_to_a_contact()
    {
        list($user, $contact) = $this->fetchUser();

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
        list($user, $contact) = $this->fetchUser();

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

    public function test_a_contact_can_have_food_preferences()
    {
        list($user, $contact) = $this->fetchUser();

        $food = ['food' => $this->faker->sentence()];

        $this->post('/people/'.$contact->hashID().'/food/save', $food);

        $food['id'] = $contact->id;
        $this->changeArrayKey('food', 'food_preferencies', $food);

        $this->assertDatabaseHas('contacts', $food);
    }

    public function test_a_contact_can_have_its_last_name_removed()
    {
        list($user, $contact) = $this->fetchUser();

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
        list($user, $contact) = $this->fetchUser();

        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $user->account->id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        config(['monica.requires_subscription' => true]);

        $response = $this->get('/people/add');

        $response->assertRedirect('/settings/subscriptions');
    }

    public function test_user_can_add_new_contacts_when_instance_requires_no_subscription()
    {
        list($user, $contact) = $this->fetchUser();

        $contacts = factory(Contact::class, 3)->create([
            'account_id' => $user->account->id,
        ]);

        config(['monica.number_of_allowed_contacts_free_account' => 1]);
        config(['monica.requires_subscription' => false]);

        $response = $this->get('/people/add');

        $response->assertStatus(200);
    }

    public function test_viewing_a_user_increments_the_number_of_views()
    {
        list($user, $contact) = $this->fetchUser();

        $this->assertDatabaseHas('contacts', [
            'number_of_views' => 0,
        ]);

        $response = $this->get('/people/'.$contact->hashID());
        $response = $this->get('/people/'.$contact->hashID());

        $this->assertDatabaseHas('contacts', [
            'number_of_views' => 2,
        ]);
    }

    private function changeArrayKey($from, $to, &$array = [])
    {
        $array[$to] = $array[$from];
        unset($array[$from]);

        return $array;
    }

    public function test_vcard_download()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/people/'.$contact->hashID().'/vcard');

        $response->assertOk();
        $response->assertHeader('Content-type', 'text/x-vcard; charset=UTF-8');
        $response->assertSee('FN:John Doe');
        $response->assertSee('N:Doe;John;;;');
    }
}
