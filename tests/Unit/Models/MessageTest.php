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
