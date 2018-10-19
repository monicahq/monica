<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Gift;
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

    public function test_gift_get_all_gifts()
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

    public function test_gift_get_contact_all_gifts()
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

    public function test_gift_get_one_gift()
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

    public function test_gift_create_gift()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/gifts', [
            'account_id' => $user->account->id,
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
            'name' => 'the gift',
            'id' => $gift_id,
        ]);
    }

    public function test_gift_update_gift()
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
            'name' => 'the gift',
            'comment' => 'one comment',
            'id' => $gift_id,
        ]);
    }

    public function test_gift_delete_gift()
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
}
