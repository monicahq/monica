<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\AddMessageToConversation;

class AddMessageToConversationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'contact_id' => 1,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(MissingParameterException::class);

        $addMessageToConversation = new AddMessageToConversation;
        $conversation = $addMessageToConversation->execute($request);
    }

    public function test_it_stores_a_message()
    {
        $conversation = factory(Conversation::class)->create([]);

        $request = [
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'conversation_id' => $conversation->id,
            'written_by_me' => true,
            'written_at' => Carbon::now(),
            'content' => 'lorem ipsum',
        ];

        $conversationService = new AddMessageToConversation;
        $message = $conversationService->execute($request);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'contact_id' => $message->contact->id,
            'account_id' => $message->account->id,
            'written_by_me' => true,
            'content' => 'lorem ipsum',
        ]);

        $this->assertInstanceOf(
            Message::class,
            $message
        );
    }

    public function test_it_throws_an_exception_if_conversation_is_not_found()
    {
        $request = [
            'conversation_id' => 12343123,
            'contact_id' => 12343123,
            'account_id' => 12343123,
            'written_by_me' => true,
            'written_at' => Carbon::now(),
            'content' => 'lorem ipsum',
        ];

        $this->expectException(ModelNotFoundException::class);

        $conversationService = new AddMessageToConversation;
        $conversation = $conversationService->execute($request);
    }
}
