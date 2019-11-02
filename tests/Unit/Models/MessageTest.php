<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MessageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $message = factory(Message::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($message->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create();
        $message = factory(Message::class)->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($message->contact()->exists());
    }

    public function test_it_belongs_to_a_conversation()
    {
        $conversation = factory(Conversation::class)->create();
        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertTrue($message->conversation()->exists());
    }

    public function test_it_gets_the_content_attribute()
    {
        $message = factory(Message::class)->create([
            'content' => 'This is a text',
        ]);

        $this->assertEquals(
            'This is a text',
            $message->content
        );
    }
}
