<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Pet;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\PetCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiPetsTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonPet = [
        'id',
        'object',
        'name',
        'pet_category' => [
            'id',
            'object',
            'name',
            'is_common',
        ],
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_pets_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet1 = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet2 = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/pets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonPet],
        ]);
        $response->assertJsonFragment([
            'object' => 'pet',
            'id' => $pet1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'pet',
            'id' => $pet2->id,
        ]);
    }

    public function test_pets_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet1 = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet2 = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/pets');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonPet],
        ]);
        $response->assertJsonFragment([
            'object' => 'pet',
            'id' => $pet1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'pet',
            'id' => $pet2->id,
        ]);
    }

    public function test_pets_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/pets');

        $this->expectNotFound($response);
    }

    public function test_pets_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet1 = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $pet2 = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/pets/'.$pet1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonPet,
        ]);
        $response->assertJsonFragment([
            'object' => 'pet',
            'id' => $pet1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'pet',
            'id' => $pet2->id,
        ]);
    }

    public function test_pets_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/pets/0');

        $this->expectNotFound($response);
    }

    public function test_pets_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet_category = factory(PetCategory::class)->create();

        $response = $this->json('POST', '/api/pets', [
            'contact_id' => $contact->id,
            'pet_category_id' => $pet_category->id,
            'name' => 'the name',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonPet,
        ]);
        $pet_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'pet',
            'id' => $pet_id,
            'name' => 'the name',
        ]);

        $this->assertGreaterThan(0, $pet_id);
        $this->assertDatabaseHas('pets', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'pet_category_id' => $pet_category->id,
            'id' => $pet_id,
            'name' => 'the name',
        ]);
    }

    public function test_pets_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/pets', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The pet category id field is required.',
        ]);
    }

    public function test_pets_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $pet_category = factory(PetCategory::class)->create();

        $response = $this->json('POST', '/api/pets', [
            'contact_id' => $contact->id,
            'pet_category_id' => $pet_category->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_pets_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $pet_category = factory(PetCategory::class)->create();

        $response = $this->json('PUT', '/api/pets/'.$pet->id, [
            'contact_id' => $contact->id,
            'pet_category_id' => $pet_category->id,
            'name' => 'the name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonPet,
        ]);
        $pet_id = $response->json('data.id');
        $this->assertEquals($pet->id, $pet_id);
        $response->assertJsonFragment([
            'object' => 'pet',
            'id' => $pet_id,
            'name' => 'the name',
        ]);

        $this->assertGreaterThan(0, $pet_id);
        $this->assertDatabaseHas('pets', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'pet_category_id' => $pet_category->id,
            'id' => $pet_id,
            'name' => 'the name',
        ]);
    }

    public function test_pets_update_error()
    {
        $user = $this->signin();
        $pet = factory(Pet::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/pets/'.$pet->id, [
            'contact_id' => $pet->contact_id,
        ]);

        $this->expectDataError($response, [
            'The pet category id field is required.',
        ]);
    }

    public function test_pets_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $pet = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $pet_category = factory(PetCategory::class)->create();

        $response = $this->json('PUT', '/api/pets/'.$pet->id, [
            'contact_id' => $contact->id,
            'pet_category_id' => $pet_category->id,
        ]);

        $this->expectNotFound($response);
    }

    public function test_pets_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $pet = factory(Pet::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('pets', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $pet->id,
        ]);

        $response = $this->json('DELETE', '/api/pets/'.$pet->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('pets', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $pet->id,
        ]);
    }

    public function test_pets_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/pets/0');

        $this->expectNotFound($response);
    }
}
