<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\AddMessageToConversation;

class AddMessageToConversationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'contact_id' => 1,
            'happened_at' => now(),
        ];

        $this->expectException(ValidationException::class);

        app(AddMessageToConversation::class)->execute($request);
    }

    /** @test */
    public function it_stores_a_message()
    {
        $conversation = factory(Conversation::class)->create([]);

        $request = [
            'account_id' => $conversation->account_id,
            'contact_id' => $conversation->contact_id,
            'conversation_id' => $conversation->id,
            'written_by_me' => true,
            'written_at' => now(),
            'content' => 'lorem ipsum',
        ];

        $message = app(AddMessageToConversation::class)->execute($request);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'contact_id' => $message->contact_id,
            'account_id' => $message->account_id,
            'written_by_me' => true,
            'content' => 'lorem ipsum',
        ]);

        $this->assertInstanceOf(
            Message::class,
            $message
        );
    }

    /** @test */
    public function it_throws_an_exception_if_contact_is_not_found()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $conversation = factory(Conversation::class)->create([
            'account_id' => $account->id,
        ]);
        $request = [
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'written_by_me' => true,
            'written_at' => now(),
            'content' => 'lorem ipsum',
        ];

        $this->expectException(ModelNotFoundException::class);

        app(AddMessageToConversation::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_conversation_is_not_found2()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $conversation = factory(Conversation::class)->create([
            'contact_id' => $contact->id,
        ]);
        $request = [
            'conversation_id' => $conversation->id,
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'written_by_me' => true,
            'written_at' => now(),
            'content' => 'lorem ipsum',
        ];

        $this->expectException(ModelNotFoundException::class);

        app(AddMessageToConversation::class)->execute($request);
    }
}
