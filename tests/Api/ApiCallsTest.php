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


namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Call;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiCallsTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonCall = [
        'id',
        'object',
        'called_at',
        'content',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_calls_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call1 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call2 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/calls');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCall],
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call2->id,
        ]);
    }

    public function test_calls_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call1 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call2 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/calls');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCall],
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'call',
            'id' => $call2->id,
        ]);
    }

    public function test_calls_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/calls');

        $this->expectNotFound($response);
    }

    public function test_calls_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call1 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $call2 = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/calls/'.$call1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonCall,
        ]);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'call',
            'id' => $call2->id,
        ]);
    }

    public function test_calls_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/calls/0');

        $this->expectNotFound($response);
    }

    public function test_calls_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/calls', [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonCall,
        ]);
        $call_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call_id,
        ]);

        $this->assertGreaterThan(0, $call_id);
        $this->assertDatabaseHas('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $call_id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);
    }

    public function test_calls_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/calls', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The content field is required.',
            'The called at field is required.',
        ]);
    }

    public function test_calls_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/calls', [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    public function test_calls_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/calls/'.$call->id, [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonCall,
        ]);
        $call_id = $response->json('data.id');
        $this->assertEquals($call->id, $call_id);
        $response->assertJsonFragment([
            'object' => 'call',
            'id' => $call_id,
        ]);

        $this->assertGreaterThan(0, $call_id);
        $this->assertDatabaseHas('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $call_id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);
    }

    public function test_calls_update_error()
    {
        $user = $this->signin();
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/calls/'.$call->id, [
            'contact_id' => $call->contact_id,
        ]);

        $this->expectDataError($response, [
            'The content field is required.',
            'The called at field is required.',
        ]);
    }

    public function test_calls_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/calls/'.$call->id, [
            'contact_id' => $contact->id,
            'content' => 'the call',
            'called_at' => '2018-05-01',
        ]);

        $this->expectNotFound($response);
    }

    public function test_calls_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $call = factory(Call::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $call->id,
        ]);

        $response = $this->json('DELETE', '/api/calls/'.$call->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('calls', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $call->id,
        ]);
    }

    public function test_calls_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/calls/0');

        $this->expectNotFound($response);
    }
}
