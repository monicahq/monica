<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ConversationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $conversation = factory(Conversation::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($conversation->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create();
        $conversation = factory(Conversation::class)->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($conversation->contact()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact_field_type()
    {
        $account = factory(Account::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);
        $conversation = factory(Conversation::class)->create([
            'contact_field_type_id' => $contactFieldType->id,
            'account_id' => $account->id,
        ]);

        $this->assertTrue($conversation->contactFieldType()->exists());
    }

    /** @test */
    public function it_has_many_messages()
    {
        $conversation = factory(Conversation::class)->create();
        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertTrue($conversation->messages()->exists());
    }
}
