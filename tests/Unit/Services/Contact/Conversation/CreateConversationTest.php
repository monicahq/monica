<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace Tests\Unit\Services\Contact\Conversation;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\CreateConversation;

class CreateConversationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_conversation()
    {
        $contact = factory(Contact::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'happened_at' => Carbon::now(),
            'contact_field_type_id' => $contactFieldType->id,
        ];

        $conversationService = new CreateConversation;
        $conversation = $conversationService->execute($request);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
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

        $this->expectException(MissingParameterException::class);

        $createConversation = new CreateConversation;
        $conversation = $createConversation->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $contact->account_id,
        ]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $account->id,
            'happened_at' => Carbon::now(),
            'contact_field_type_id' => $contactFieldType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        $createConversation = (new CreateConversation)->execute($request);
    }

    public function test_it_throws_an_exception_if_contactfieldtype_is_not_linked_to_account()
    {
        $contact = factory(Contact::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([]);

        $request = [
            'contact_id' => $contact->id,
            'account_id' => $contact->account->id,
            'happened_at' => Carbon::now(),
            'contact_field_type_id' => $contactFieldType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        $createConversation = new CreateConversation;
        $conversation = $createConversation->execute($request);
    }
}
