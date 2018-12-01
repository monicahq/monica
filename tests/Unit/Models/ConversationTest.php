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

    public function test_it_belongs_to_a_contact_field_type()
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

    public function test_it_has_many_messages()
    {
        $conversation = factory(Conversation::class)->create();
        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
        ]);

        $this->assertTrue($conversation->messages()->exists());
    }
}
