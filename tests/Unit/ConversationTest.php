<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConversationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $conversation = factory(Conversation::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($conversation->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create();
        $conversation = factory(Conversation::class)->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($conversation->contact()->exists());
    }

    public function test_it_has_many_messages()
    {
        $conversation = factory(Conversation::class)->create();
        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertTrue($conversation->messages()->exists());
    }
}
