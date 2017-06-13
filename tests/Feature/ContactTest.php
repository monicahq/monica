<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactTest extends FeatureTestCase
{

    use DatabaseTransactions;

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
     * @return array
     */
    private function createUserAndContact()
    {
        $data['user'] = $this->signIn();

        $data['contact'] = factory('App\Contact')->create([
            'account_id' => $data['user']->account_id
        ]);

        return $data;
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_user_can_see_contacts()
    {
        extract($this->createUserAndContact());

        $response = $this->get('/people');

        $response->assertSee(
            $contact->first_name . ' ' . $contact->middle_name . ' ' . $contact->last_name
        );
    }

    public function test_user_can_add_a_contact()
    {
        $user = $this->signIn();

        $params = [
            'gender' => 'male',
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName
        ];

         $this->post('/people', $params);

         // Assert the contact has been added for the correct user.
         $params['account_id'] = $user->account_id;

         $this->assertDatabaseHas('contacts', $params);
    }

    public function test_user_can_add_note_to_contact()
    {
        extract($this->createUserAndContact());

        $body = $this->faker->paragraph();

        $this->post('/people/' . $contact->id . '/note/save', [
            'body' => $body
        ]);

        $this->assertDatabaseHas('notes', [
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'body' => $body
        ]);

    }

    public function test_user_can_add_activity_to_contact()
    {
        extract($this->createUserAndContact());

        $activity = [
            'summary' => $this->faker->sentence('5'),
            'specific_date' => $this->faker->date('Y-m-d'),
            'comment' => $this->faker->paragraph()
        ];

        $this->post(
            '/people/' . $contact->id . '/activities/store',
                $activity
        );

        $activity['contact_id'] = $contact->id;
        $activity['account_id'] = $user->account_id;

        unset($activity['specific_date']);

        // The name of the form element is different to the name of the
        // database table so this must be changed.
        $this->changeArrayKey('comment', 'description', $activity);

        $this->assertDatabaseHas('activities', $activity);
    }

    public function test_user_can_be_reminded_about_an_event_once()
    {
        extract($this->createUserAndContact());

        $reminder = [
            'reminder' => $this->faker->sentence('5'),
            'reminderNextExpectedDate' => $this->faker->dateTimeBetween('now', '+2 years'),
            'frequencyType' => 'once',
            'comment' => $this->faker->sentence()
        ];

        $this->post(
            '/people/' . $contact->id . '/reminders/store',
            $reminder
        );

        $reminder['contact_id'] = $contact->id;
        $reminder['account_id'] = $user->account_id;

        $this->changeArrayKey('reminder', 'title', $reminder);
        $this->changeArrayKey('comment', 'description', $reminder);
        $this->changeArrayKey('frequencyType', 'frequency_type', $reminder);
        $reminder['frequency_type'] = 'one_time';
        unset($reminder['reminderNextExpectedDate']);

        $this->assertDatabaseHas('reminders', $reminder);

    }

    public function test_user_can_add_a_task_to_a_contact()
    {
        extract($this->createUserAndContact());

        $task = [
            'title' => $this->faker->sentence(),
            'comment' => $this->faker->sentence(3)
        ];

        $this->post(
            '/people/' . $contact->id . '/tasks/store',
            $task
        );

        $task['contact_id'] = $contact->id;
        $task['account_id'] = $user->account_id;

        // Change keys to match database column names
        $this->changeArrayKey('comment', 'description', $task);

        $this->assertDatabaseHas('tasks', $task);
    }

    public function test_user_can_add_a_gift_idea_to_a_contact()
    {
        $user = $this->signIn();

        $contact = factory('App\Contact')->create([
            'account_id' => $user->account_id
        ]);

        $gift = [
            'gift-offered' => 'is_an_idea',
            'title' => $this->faker->word,
            'url' => $this->faker->url,
            'value' => $this->faker->numberBetween(1,2000),
            'comment' => $this->faker->sentence()
        ];

        $this->post(
            '/people/' . $contact->id . '/gifts/store',
            $gift
        );

        $gift['contact_id'] = $contact->id;
        $gift['account_id'] = $user->account_id;


        // Change values to match database column names.
        $this->changeArrayKey('title', 'name', $gift);
        $this->changeArrayKey('value', 'value_in_dollars', $gift);
        $gift['is_an_idea'] = "true";
        unset($gift['gift-offered']);

        $this->assertDatabaseHas('gifts', $gift);
    }

    public function test_user_can_be_in_debt_to_a_contact()
    {
        extract($this->createUserAndContact());

        $debt = [
            'in-debt' => 'yes',
            'amount' => $this->faker->numberBetween(1,5000),
            'reason' => $this->faker->sentence()
        ];

        $this->post(
            '/people/' . $contact->id . '/debt/store',
            $debt
        );

        $debt['account_id'] = $user->account_id;
        $debt['contact_id'] = $contact->id;
        $debt['in_debt'] = 'yes';

        unset($debt['in-debt']);

        $this->assertDatabaseHas('debts', $debt);
    }

    public function test_user_can_be_owed_debt_by_a_contact()
    {
        extract($this->createUserAndContact());

        $debt = [
            'in-debt' => 'no',
            'amount' => $this->faker->numberBetween(1,5000),
            'reason' => $this->faker->sentence()
        ];

        $this->post(
            '/people/' . $contact->id . '/debt/store',
            $debt
        );

        $debt['account_id'] = $user->account_id;
        $debt['contact_id'] = $contact->id;
        $debt['in_debt'] = 'no';

        unset($debt['in-debt']);

        $this->assertDatabaseHas('debts', $debt);
    }


    public function test_a_contact_can_have_food_preferences()
    {
        extract($this->createUserAndContact());

        $food = ['food' => $this->faker->sentence()];

        $this->post('/people/' . $contact->id . '/food/save', $food);


        $food['id'] = $contact->id;
        $this->changeArrayKey('food', 'food_preferencies', $food);

        $this->assertDatabaseHas('contacts', $food);
    }

    public function test_a_contact_can_be_deleted()
    {
        extract($this->createUserAndContact());

        $this->get('/people/' . $contact->id . '/delete');

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id
        ]);
    }

    private function changeArrayKey($from, $to, &$array = [])
    {
        $array[$to] = $array[$from];
        unset($array[$from]);

        return $array;
    }
}
