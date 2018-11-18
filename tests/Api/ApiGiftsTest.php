<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Gift;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiGiftsTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonGift = [
        'id',
        'object',
        'date_offered',
        'has_been_offered',
        'comment',
        'is_an_idea',
        'is_for',
        'name',
        'url',
        'value',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_gifts_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift1 = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift2 = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/gifts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonGift],
        ]);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift2->id,
        ]);
    }

    public function test_gifts_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift1 = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift2 = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/gifts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonGift],
        ]);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'gift',
            'id' => $gift2->id,
        ]);
    }

    public function test_gifts_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/gifts');

        $this->expectNotFound($response);
    }

    public function test_gifts_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift1 = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $gift2 = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/gifts/'.$gift1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonGift,
        ]);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'gift',
            'id' => $gift2->id,
        ]);
    }

    public function test_gifts_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/gifts/0');

        $this->expectNotFound($response);
    }

    public function test_gifts_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/gifts', [
            'contact_id' => $contact->id,
            'name' => 'the gift',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonGift,
        ]);
        $gift_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift_id,
        ]);

        $this->assertGreaterThan(0, $gift_id);
        $this->assertDatabaseHas('gifts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $gift_id,
            'name' => 'the gift',
        ]);
    }

    public function test_gifts_create_is_for()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/gifts', [
            'contact_id' => $contact->id,
            'name' => 'the gift',
            'is_for' => $contact2->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonGift,
        ]);
        $gift_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift_id,
        ]);

        $this->assertGreaterThan(0, $gift_id);
        $this->assertDatabaseHas('gifts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $gift_id,
            'name' => 'the gift',
            'is_for' => $contact2->id,
        ]);
    }

    public function test_gifts_create_is_for_bad_account()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $account = factory(Account::class)->create();
        $contact2 = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/gifts', [
            'contact_id' => $contact->id,
            'name' => 'the gift',
            'is_for' => $contact2->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_gifts_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/gifts', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_gifts_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/gifts', [
            'contact_id' => $contact->id,
            'name' => 'the gift',
        ]);

        $this->expectNotFound($response);
    }

    public function test_gifts_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/gifts/'.$gift->id, [
            'contact_id' => $contact->id,
            'name' => 'the gift',
            'comment' => 'one comment',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonGift,
        ]);
        $gift_id = $response->json('data.id');
        $this->assertEquals($gift->id, $gift_id);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift_id,
        ]);

        $this->assertGreaterThan(0, $gift_id);
        $this->assertDatabaseHas('gifts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $gift_id,
            'name' => 'the gift',
            'comment' => 'one comment',
        ]);
    }

    public function test_gifts_update_is_for()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/gifts/'.$gift->id, [
            'contact_id' => $contact->id,
            'name' => 'the gift',
            'comment' => 'one comment',
            'is_for' => $contact2->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonGift,
        ]);
        $gift_id = $response->json('data.id');
        $this->assertEquals($gift->id, $gift_id);
        $response->assertJsonFragment([
            'object' => 'gift',
            'id' => $gift_id,
        ]);

        $this->assertGreaterThan(0, $gift_id);
        $this->assertDatabaseHas('gifts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $gift_id,
            'name' => 'the gift',
            'comment' => 'one comment',
            'is_for' => $contact2->id,
        ]);
    }

    public function test_gifts_update_error()
    {
        $user = $this->signin();
        $gift = factory(Gift::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/gifts/'.$gift->id, [
            'contact_id' => $gift->contact_id,
        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    public function test_gifts_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $gift = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/gifts/'.$gift->id, [
            'contact_id' => $contact->id,
            'name' => 'the gift',
            'comment' => 'one comment',
        ]);

        $this->expectNotFound($response);
    }

    public function test_gifts_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $gift = factory(Gift::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('gifts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $gift->id,
        ]);

        $response = $this->json('DELETE', '/api/gifts/'.$gift->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('gifts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $gift->id,
        ]);
    }

    public function test_gifts_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/gifts/0');

        $this->expectNotFound($response);
    }
}
