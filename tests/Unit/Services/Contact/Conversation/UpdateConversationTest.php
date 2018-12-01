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

        $conversationService = new UpdateConversation;
        $conversation = $conversationService->execute($request);

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

        $this->expectException(MissingParameterException::class);

        $updateConversation = new UpdateConversation;
        $conversation = $updateConversation->execute($request);
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

        $updateConversation = new UpdateConversation;
        $conversation = $updateConversation->execute($request);
    }
}
