<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\DestroyConversation;

class DestroyConversationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_conversation()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $conversation->account_id,
            'conversation_id' => $conversation->id,
        ];

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
        ]);

        app(DestroyConversation::class)->execute($request);

        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);
    }

    /** @test */
    public function destroying_a_conversation_destroys_corresponding_messages()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
            'account_id' => $conversation->account_id,
            'contact_id' => $conversation->contact_id,
            'content' => 'tititi',
            'written_at' => '2009-01-01',
            'written_by_me' => false,
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
        ]);

        $request = [
            'account_id' => $conversation->account_id,
            'conversation_id' => $conversation->id,
        ];

        app(DestroyConversation::class)->execute($request);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $conversation->account_id,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyConversation::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_conversation_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $conversation = factory(Conversation::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'conversation_id' => $conversation->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(DestroyConversation::class)->execute($request);
    }
}
