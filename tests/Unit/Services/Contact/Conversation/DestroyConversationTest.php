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

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Message;
use App\Models\Contact\Conversation;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Conversation\DestroyConversation;

class DestroyConversationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_conversation()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $conversation->account->id,
            'conversation_id' => $conversation->id,
        ];

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
        ]);

        $conversationService = new DestroyConversation;
        $bool = $conversationService->execute($request);

        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_destroying_a_conversation_destroys_corresponding_messages()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $message = factory(Message::class)->create([
            'conversation_id' => $conversation->id,
            'account_id' => $conversation->account->id,
            'contact_id' => $conversation->contact->id,
            'content' => 'tititi',
            'written_at' => '2009-01-01',
            'written_by_me' => false,
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
        ]);

        $request = [
            'account_id' => $conversation->account->id,
            'conversation_id' => $conversation->id,
        ];

        $conversationService = (new DestroyConversation)->execute($request);

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $conversation = factory(Conversation::class)->create([
            'happened_at' => '2008-01-01',
        ]);

        $request = [
            'account_id' => $conversation->account->id,
        ];

        $this->expectException(MissingParameterException::class);

        $conversationService = (new DestroyConversation)->execute($request);
    }

    public function test_it_throws_an_exception_if_conversation_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $conversation = factory(Conversation::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'conversation_id' => $conversation->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyConversation = (new DestroyConversation)->execute($request);
    }
}
