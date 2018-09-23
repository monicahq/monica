<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Conversation\DestroyMessage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_message()
    {
        $conversation = factory(Conversation::class)->create([]);

        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'content' => 'tititi',
            'written_at' => '2009-01-01',
            'written_by_me' => false,
        ]);

        $request = [
            'account_id' => $conversation->account->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ];

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
        ]);

        $messageService = new DestroyMessage;
        $bool = $messageService->execute($request);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'conversation_id' => 2,
            'message_id' => 3,
        ];

        $this->expectException(MissingParameterException::class);

        $destroyMessage = new DestroyMessage;
        $result = $destroyMessage->execute($request);
    }

    public function test_it_throws_an_exception_if_message_doesnt_exist()
    {
        $conversation = factory(Conversation::class)->create([]);

        $request = [
            'account_id' => $conversation->account->id,
            'conversation_id' => $conversation->id,
            'message_id' => -1,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyMessage = new DestroyMessage;
        $message = $destroyMessage->execute($request);
    }
}
