<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\LifeEvent;
use App\Models\Contact\LifeEventType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiLifeEventControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonLifeEvents = [
        'id',
        'object',
        'name',
        'note',
        'happened_at',
        'life_event_type' => [
            'id',
        ],
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    private function createLifeEvent(User $user): LifeEvent
    {
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $lifeEvent = factory(LifeEvent::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => now(),
            'name' => 'This is a text',
            'note' => 'This is a text',
        ]);

        return $lifeEvent;
    }

    public function test_it_gets_a_list_of_life_events()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createLifeEvent($user);
        }

        $response = $this->json('GET', '/api/lifeevents');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonLifeEvents,
            ],
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createLifeEvent($user);
        }

        $response = $this->json('GET', '/api/lifeevents?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/lifeevents?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_gets_a_life_event()
    {
        $user = $this->signin();

        $lifeEvent = $this->createLifeEvent($user);

        $response = $this->json('GET', '/api/lifeevents/'.$lifeEvent->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonLifeEvents,
        ]);
    }

    public function test_getting_a_life_event_doesnt_work_if_life_event_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/lifeevents/329029093809');

        $this->expectNotFound($response);
    }

    public function test_it_creates_a_life_event()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $response = $this->json('POST', '/api/lifeevents', [
            'contact_id' => $contact->id,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => '1989-02-02',
            'name' => 'This is a text',
            'note' => 'This is a text',
            'has_reminder' => false,
            'happened_at_month_unknown' => false,
            'happened_at_day_unknown' => false,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonLifeEvents,
        ]);
    }

    public function test_creating_a_life_event_doesnt_work_if_ids_are_not_found()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/lifeevents', [
            'contact_id' => $contact->id,
            'life_event_type_id' => 0,
            'happened_at' => '1989-02-02',
            'name' => 'This is a text',
            'note' => 'This is a text',
            'has_reminder' => false,
            'happened_at_month_unknown' => false,
            'happened_at_day_unknown' => false,
        ]);

        $this->expectNotFound($response);

        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $response = $this->json('POST', '/api/lifeevents', [
            'contact_id' => 0,
            'life_event_type_id' => $lifeEventType->id,
            'happened_at' => '1989-02-02',
            'name' => 'This is a text',
            'note' => 'This is a text',
            'has_reminder' => false,
            'happened_at_month_unknown' => false,
            'happened_at_day_unknown' => false,
        ]);

        $this->expectNotFound($response);
    }

    public function test_creating_a_life_event_doesnt_work_if_parameters_are_not_right()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $response = $this->json('POST', '/api/lifeevents', [
            'contact_id' => $contact->id,
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a text',
            'note' => 'This is a text',
        ]);

        $this->expectDataError($response, [
            'The happened at field is required.',
            'The has reminder field is required.',
            'The happened at month unknown field is required.',
            'The happened at day unknown field is required.',
        ]);
    }

    public function test_it_updates_a_life_event()
    {
        $user = $this->signin();

        $lifeEvent = $this->createLifeEvent($user);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/lifeevents/'.$lifeEvent->id, [
            'happened_at' =>  '1989-02-02',
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a text',
            'note' => 'This is a text',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonLifeEvents,
        ]);
    }

    public function test_updating_a_life_event_doesnt_work_if_ids_are_not_found()
    {
        $user = $this->signin();

        $lifeEvent = $this->createLifeEvent($user);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/lifeevents/23929390', [
            'happened_at' =>  '1989-02-02',
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a text',
            'note' => 'This is a text',
        ]);

        $this->expectNotFound($response);

        $response = $this->json('PUT', '/api/lifeevents/'.$lifeEvent->id, [
            'happened_at' => '1989-02-02',
            'life_event_type_id' => 3283028,
            'name' => 'This is a text',
            'note' => 'This is a text',
        ]);

        $this->expectNotFound($response);
    }

    public function test_updating_a_life_event_doesnt_work_if_parameters_are_not_right()
    {
        $user = $this->signin();

        $lifeEvent = $this->createLifeEvent($user);
        $lifeEventType = factory(LifeEventType::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/lifeevents/'.$lifeEvent->id, [
            'life_event_type_id' => $lifeEventType->id,
            'name' => 'This is a text',
            'note' => 'This is a text',
        ]);

        $this->expectDataError($response, [
            'The happened at field is required.',
        ]);
    }

    public function test_it_destroys_a_life_event()
    {
        $user = $this->signin();

        $lifeEvent = $this->createLifeEvent($user);

        $response = $this->delete('/api/lifeevents/'.$lifeEvent->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $lifeEvent->id,
        ]);
    }

    public function test_deleting_a_life_event_doesnt_work_if_ids_are_not_found()
    {
        $user = $this->signin();

        $lifeEvent = $this->createLifeEvent($user);

        $response = $this->delete('/api/lifeevents/39230990');

        $this->expectNotFound($response);
    }
}
