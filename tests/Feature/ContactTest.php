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
        ];

        $this->post(
            '/people/'.$contact->id.'/tasks',
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
            '/people/'.$contact->id.'/gifts/store',
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

    public function test_user_can_edit_a_gift_()
    {
        list($user, $contact) = $this->fetchUser();

        $old_gift = factory(Gift::class)->create([
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

        $this->post(
            '/people/'.$contact->id.'/gifts/'.$old_gift->id.'/update',
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

    public function test_user_can_be_in_debt_to_a_contact()
    {
        list($user, $contact) = $this->fetchUser();

        $debt = [
            'in_debt' => 'yes',
            'amount' => $this->faker->numberBetween(1, 5000),
            'reason' => $this->faker->sentence(),
        ];

        $this->post(
            route('people.debt.store', $contact),
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
            route('people.debt.store', $contact),
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

        $this->post('/people/'.$contact->id.'/food/save', $food);

        $food['id'] = $contact->id;
        $this->changeArrayKey('food', 'food_preferencies', $food);

        $this->assertDatabaseHas('contacts', $food);
    }

    private function changeArrayKey($from, $to, &$array = [])
    {
        $array[$to] = $array[$from];
        unset($array[$from]);

        return $array;
    }
}
