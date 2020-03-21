<?php

namespace Tests\Api\ContactField;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactFieldControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonContactField = [
        'id',
        'object',
        'content',
        'contact_field_type',
        'account' => [
            'id',
        ],
        'contact',
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function contact_fields_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField1 = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField2 = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/contactfields');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonContactField],
        ]);
        $response->assertJsonFragment([
            'object' => 'contactfield',
            'id' => $contactField1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'contactfield',
            'id' => $contactField2->id,
        ]);
    }

    /** @test */
    public function contact_fields_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/contactfields');

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_fields_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField1 = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contactField2 = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/contactfields/'.$contactField1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonContactField,
        ]);
        $response->assertJsonFragment([
            'object' => 'contactfield',
            'id' => $contactField1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'contactfield',
            'id' => $contactField2->id,
        ]);
    }

    /** @test */
    public function contact_fields_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contactfields/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_fields_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contactfields', [
            'contact_id' => $contact->id,
            'contact_field_type_id' => $field->id,
            'data' => 'ok',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonContactField,
        ]);
        $contactField_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'contactfield',
            'id' => $contactField_id,
        ]);

        $this->assertGreaterThan(0, $contactField_id);
        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $contactField_id,
            'contact_field_type_id' => $field->id,
            'data' => 'ok',
        ]);
    }

    /** @test */
    public function contact_fields_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contactfields', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The contact field type id field is required.',
            'The data field is required.',
        ]);
    }

    /** @test */
    public function contact_fields_create_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/contactfields', [
            'contact_id' => $contact->id,
            'contact_field_type_id' => $field->id,
            'data' => 'ok',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_fields_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/contactfields/'.$contactField->id, [
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactField->contact_field_type_id,
            'data' => 'ok',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonContactField,
        ]);
        $contactField_id = $response->json('data.id');
        $this->assertEquals($contactField->id, $contactField_id);
        $response->assertJsonFragment([
            'object' => 'contactfield',
            'id' => $contactField_id,
        ]);

        $this->assertGreaterThan(0, $contactField_id);
        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $contactField_id,
            'contact_field_type_id' => $contactField->contact_field_type_id,
            'data' => 'ok',
        ]);
    }

    /** @test */
    public function contact_fields_update_error()
    {
        $user = $this->signin();
        $contactField = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/contactfields/'.$contactField->id, [
            'contact_id' => $contactField->contact_id,
        ]);

        $this->expectDataError($response, [
            'The contact field type id field is required.',
            'The data field is required.',
        ]);
    }

    /** @test */
    public function contact_fields_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/contactfields/'.$contactField->id, [
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactField->contact_field_type_id,
            'data' => 'ok',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function contact_fields_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('contact_fields', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $contactField->id,
        ]);

        $response = $this->json('DELETE', '/api/contactfields/'.$contactField->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('contact_fields', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $contactField->id,
        ]);
    }

    /** @test */
    public function contact_fields_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/contactfields/0');

        $this->expectDataError($response, [
            'The selected contact field id is invalid.',
        ]);
    }
}
