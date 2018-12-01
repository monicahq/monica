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
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Conversation\UpdateMessage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_conversation()
    {
        $conversation = factory(Conversation::class)->create([]);

        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'content' => 'tititi',
            'written_at' => '2009-01-01',
            'written_by_me' => false,
        ]);

        $request = [
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'written_at' => Carbon::now(),
            'written_by_me' => true,
            'content' => 'lorem',
        ];

        $messageService = new UpdateMessage;
        $message = $messageService->execute($request);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'conversation_id' => $conversation->id,
            'written_by_me' => true,
            'content' => 'lorem',
        ]);

        $this->assertInstanceOf(
            Message::class,
            $message
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'conversation_id' => 2,
            'message_id' => 3,
            'written_at' => Carbon::now(),
            'written_by_me' => true,
            'content' => 'lorem',
        ];

        $this->expectException(MissingParameterException::class);

        $updateMessage = (new UpdateMessage)->execute($request);
    }

    public function test_it_throws_an_exception_if_message_does_not_exist()
    {
        $account = factory(Account::class)->create();
        $message = factory(Message::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => 123,
            'conversation_id' => 123,
            'message_id' => $message->id,
            'written_at' => Carbon::now(),
            'written_by_me' => true,
            'content' => 'lorem',
        ];

        $this->expectException(ModelNotFoundException::class);

        $updateMessage = (new UpdateMessage)->execute($request);
    }
}
