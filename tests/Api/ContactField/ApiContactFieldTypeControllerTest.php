<?php

namespace Tests\Api\ContactField;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactFieldTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonContactFieldType = [
        'id',
        'object',
        'name',
        'protocol',
        'delible',
        'type',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function contact_field_type_get_one()
    {
        $user = $this->signin();
        $contactFieldType1 = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactFieldType2 = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/contactfieldtypes/'.$contactFieldType1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonContactFieldType,
        ]);
        $response->assertJsonFragment([
            'object' => 'contactfieldtype',
            'id' => $contactFieldType1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'contactfieldtype',
            'id' => $contactFieldType2->id,
        ]);
    }

    /** @test */
    public function contact_field_type_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contactfieldtypes/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_field_type_create()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/contactfieldtypes', [
            'name' => 'Email',
            'protocol' => 'mailto:',
            'type' => 'email',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonContactFieldType,
        ]);
        $contactFieldTypeId = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contactfieldtype',
            'id' => $contactFieldTypeId,
        ]);

        $this->assertGreaterThan(0, $contactFieldTypeId);
        $this->assertDatabaseHas('contact_field_types', [
            'account_id' => $user->account_id,
            'id' => $contactFieldTypeId,
            'name' => 'Email',
            'protocol' => 'mailto:',
            'type' => 'email',
        ]);
    }

    /** @test */
    public function contact_field_type_create_error()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/contactfieldtypes', [
        ]);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    /** @test */
    public function contact_field_type_update()
    {
        $user = $this->signin();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contactfieldtypes/'.$contactFieldType->id, [
            'name' => 'Email2',
            'protocol' => 'mailto:',
            'type' => 'email',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonContactFieldType,
        ]);
        $contactFieldTypeId = $response->json('data.id');
        $this->assertEquals($contactFieldType->id, $contactFieldTypeId);
        $response->assertJsonFragment([
            'object' => 'contactfieldtype',
            'id' => $contactFieldTypeId,
        ]);

        $this->assertGreaterThan(0, $contactFieldTypeId);
        $this->assertDatabaseHas('contact_field_types', [
            'account_id' => $user->account_id,
            'id' => $contactFieldType->id,
            'name' => 'Email2',
            'protocol' => 'mailto:',
            'type' => 'email',
        ]);
    }

    /** @test */
    public function contact_field_type_update_error()
    {
        $user = $this->signin();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contactfieldtypes/'.$contactFieldType->id, []);

        $this->expectDataError($response, [
            'The name field is required.',
        ]);
    }

    /** @test */
    public function contact_field_type_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('PUT', '/api/contactfieldtypes/'.$contactFieldType->id, [
            'name' => 'Email2',
            'protocol' => 'mailto:',
            'type' => 'email',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_field_type_delete()
    {
        $user = $this->signin();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $this->assertDatabaseHas('contact_field_types', [
            'account_id' => $user->account_id,
            'id' => $contactFieldType->id,
        ]);

        $response = $this->json('DELETE', '/api/contactfieldtypes/'.$contactFieldType->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('contact_field_types', [
            'account_id' => $user->account_id,
            'id' => $contactFieldType->id,
        ]);
    }

    /** @test */
    public function contact_field_type_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/contactfieldtypes/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_field_type_delete_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('DELETE', '/api/contactfieldtypes/'.$contactFieldType->id);

        $this->expectNotFound($response);
    }
}
