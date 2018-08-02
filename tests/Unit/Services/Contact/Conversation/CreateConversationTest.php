<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\CreateConversation;

class CreateConversationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_conversation()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'happened_at' => Carbon::now(),
        ];

        $conversationService = new CreateConversation;
        $conversation = $conversationService->execute($request);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
        ]);

        $this->assertInstanceOf(
            Conversation::class,
            $conversation
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(\Exception::class);

        $createConversation = new CreateConversation;
        $conversation = $createConversation->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $contact = factory(Contact::class)->create([
            'account_id' => 1,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => 2,
            'happened_at' => Carbon::now(),
        ];

        $this->expectException(ModelNotFoundException::class);

        $createConversation = new CreateConversation;
        $conversation = $createConversation->execute($request);
    }
}
