<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiConversationControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonConversations = [
        'id',
        'object',
        'happened_at',
        'messages',
        'contact_field_type' => [
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

    private function createConversation(User $user): Conversation
    {
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $conversation = factory(Conversation::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'happened_at' => \Carbon\Carbon::now(),
        ]);

        return $conversation;
    }

    public function test_it_gets_a_list_of_conversations()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createConversation($user);
        }

        $response = $this->json('GET', '/api/conversations');

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
                '*' => $this->jsonConversations,
            ],
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createConversation($user);
        }

        $response = $this->json('GET', '/api/conversations?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/conversations?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_gets_a_conversation()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);

        $response = $this->json('GET', '/api/conversations/'.$conversation->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonConversations,
        ]);
    }

    public function test_it_gets_a_conversation_for_a_specific_contact()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);

        $response = $this->json('GET', '/api/contacts/'.$conversation['contact_id'].'/conversations');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonConversations,
            ],
        ]);
    }

    public function test_it_creates_a_conversation()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $response = $this->json('POST', '/api/conversations', [
            'contact_id' => $contact->id,
            'happened_at' => '1989-02-02',
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonConversations,
        ]);
    }

    public function test_it_updates_a_conversation()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/conversations/'.$conversation->id, [
            'happened_at' => '1989-02-02',
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonConversations,
        ]);
    }

    public function test_it_destroys_a_conversation()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);

        $response = $this->delete('/api/conversations/'.$conversation->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $conversation->id,
        ]);
    }
}
