<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiMessageControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonConversations = [
        'id',
        'object',
        'happened_at',
        'messages' => [
            [
                'id',
            ],
        ],
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

    private function addMessage(Conversation $conversation): Message
    {
        $message = factory(Message::class)->create([
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'conversation_id' => $conversation->id,
        ]);

        return $message;
    }

    public function test_it_adds_a_message_to_a_conversation()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);

        $response = $this->json('POST', '/api/conversations/'.$conversation->id.'/messages', [
            'written_at' => '1998-02-02',
            'written_by_me' => true,
            'content' => 'lorem ipsum',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonConversations,
        ]);
    }

    public function test_it_updates_a_message()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);
        $message = $this->addMessage($conversation);

        $response = $this->json('PUT', '/api/conversations/'.$conversation->id.'/messages/'.$message->id, [
            'written_at' => '1989-02-02',
            'written_by_me' => true,
            'content' => 'lorem ipsum',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonConversations,
        ]);
    }

    public function test_it_destroys_a_message()
    {
        $user = $this->signin();

        $conversation = $this->createConversation($user);
        $message = $this->addMessage($conversation);

        $response = $this->delete('/api/conversations/'.$conversation->id.'/messages/'.$message->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $message->id,
        ]);
    }
}
