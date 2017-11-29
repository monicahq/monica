<?php

namespace Tests\Feature;

use App\Contact;
use Tests\FeatureTestCase;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TaskTest extends FeatureTestCase
{
    use DatabaseTransactions;

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

    public function test_user_can_add_a_task()
    {
        list($user, $contact) = $this->fetchUser();

        $faker = Faker::create();
        $taskTitle = $faker->realText();
        $taskDescription = $faker->realText();

        $params = [
            'title' => $taskTitle,
            'description' => $taskDescription,
            'completed' => 0,
        ];

        $response = $this->post('/people/'.$contact->id.'/tasks', $params);

        // Assert the note has been added for the correct user.
        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['title'] = $taskTitle;
        $params['description'] = $taskDescription;

        $this->assertDatabaseHas('tasks', $params);

        // Make sure an event has been created for this action
        $eventParams['account_id'] = $user->account_id;
        $eventParams['contact_id'] = $contact->id;
        $eventParams['object_type'] = 'task';
        $eventParams['nature_of_operation'] = 'create';
        $this->assertDatabaseHas('events', $eventParams);

        // Visit the dashboard and checks that the note event appears on the
        // dashboard
        $response = $this->get('/dashboard');
        $response->assertSee('added a task');
        $response->assertSee('<a href="/people/'.$contact->id);
    }
}
