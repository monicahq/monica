<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Conversation;
use App\Models\Contact\Contact;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\Conversation\UpdateConversation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateConversationTest extends TestCase
{
    use DatabaseTransactions;

    protected $jsonStructureConversation = [
        'account_id',
        'happened_at',
    ];

    public function test_it_updates_a_conversation()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $conversation->account->id,
            'conversation_id' => $conversation->id,
            'happened_at' => '2010-02-02',
        ];

        $conversationService = new UpdateConversation;
        $conversation = $conversationService->execute($request);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'happened_at' => '2010-02-02 00:00:00',
        ]);

        $this->assertInstanceOf(
            Conversation::class,
            $conversation
        );
    }

    // public function test_it_fails_if_wrong_parameters_are_given()
    // {
    //     $contact = factory(Contact::class)->create([]);

    //     $request = [
    //         'contact_id' => $contact->id,
    //         'happened_at' => Carbon::now(),
    //     ];

    //     $this->expectException(\Exception::class);

    //     $createConversation = new CreateConversation;
    //     $conversation = $createConversation->execute($request);
    // }

    // public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    // {
    //     $contact = factory(Contact::class)->create([
    //         'account_id' => 1,
    //     ]);

    //     $request = [
    //         'contact_id' => $contact->id,
    //         'account_id' => 2,
    //         'happened_at' => Carbon::now(),
    //     ];

    //     $this->expectException(ModelNotFoundException::class);

    //     $createConversation = new CreateConversation;
    //     $conversation = $createConversation->execute($request);
    // }
}
