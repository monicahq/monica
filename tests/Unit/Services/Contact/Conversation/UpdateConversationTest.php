<?php

namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\UpdateConversation;

class UpdateConversationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_conversation()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $conversation->account->id,
        ]);

        $request = [
            'account_id' => $conversation->account->id,
            'conversation_id' => $conversation->id,
            'happened_at' => '2010-02-02',
            'contact_field_type_id' => $contactFieldType->id,
        ];

        $conversation = app(UpdateConversation::class)->execute($request);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'happened_at' => '2010-02-02 00:00:00',
            'contact_field_type_id' => $contactFieldType->id,
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

        $this->expectException(ValidationException::class);

        app(UpdateConversation::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_conversation_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $conversation = factory(Conversation::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $conversation->account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'conversation_id' => $conversation->id,
            'happened_at' => '2010-02-02',
            'contact_field_type_id' => $contactFieldType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(UpdateConversation::class)->execute($request);
    }
}
