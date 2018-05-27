<?php

namespace Tests\Api\Setings\CustomField;

use Tests\ApiTestCase;
use App\Models\Settings\CustomFields\Field;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiFieldControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureField = [
        'id',
        'object',
        'name',
        'description',
        'required',
        'default_custom_field_type',
        'custom_field' => [
            'id',
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_fields()
    {
        $user = $this->signin();

        $fields = factory(Field::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/fields');

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureField,
            ],
        ]);
    }

    public function test_it_applies_limit_parameter()
    {
        $user = $this->signin();

        $fields = factory(Field::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/fields?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/fields?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_stores_a_field()
    {
        $user = $this->signin();

        $customField = factory(CustomField::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/fields', [
                            'name' => 'Movies',
                            'description' => 'this is a description',
                            'required' => false,
                            'custom_field_id' => $customField->id,
                            'custom_field_type' => $defaultCustomFieldType->id,
                        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('fields', [
            'name' => 'Movies',
            'description' => 'this is a description',
            'required' => false,
            'custom_field_id' => $customField->id,
            'default_custom_field_type_id' => $defaultCustomFieldType->id,
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureField,
        ]);
    }

    public function test_it_doesnt_store_a_field_if_query_not_valid()
    {
        $user = $this->signin();

        $customField = factory(CustomField::class)->create([
            'account_id' => $user->account_id,
        ]);

        $defaultCustomFieldType = factory(DefaultCustomFieldType::class)->create([]);

        $response = $this->json('POST', '/api/fields', [
                            'name' => 'Movies',
                            'description' => 'this is a description',
                            'custom_field_id' => $customField->id,
                            'default_custom_field_type_id' => $defaultCustomFieldType->id,
                        ]);

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => ['The required field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_updates_a_field()
    {
        $user = $this->signin();

        $field = factory(Field::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/fields/'.$field->id, [
                            'name' => 'Movies',
                            'description' => 'this is a description',
                            'required' => false,
                            'custom_field_id' => $field->custom_field_id,
                            'default_custom_field_type_id' => $field->default_custom_field_type_id,
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('fields', [
            'name' => 'Movies',
            'description' => 'this is a description',
            'required' => false,
            'custom_field_id' => $field->custom_field_id,
            'default_custom_field_type_id' => $field->default_custom_field_type_id,
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureField,
        ]);
    }

    public function test_it_doesnt_update_if_field_not_found()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/fields/2349273984279348', [
                            'name' => 'Movies',
                            'description' => 'this is a description',
                            'required' => false,
                        ]);

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_doesnt_update_if_query_is_invalid()
    {
        $user = $this->signin();

        $field = factory(Field::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/fields/'.$field->id, [
                            'name' => 'Movies',
                            'description' => 'this is a description',
                            'required' => false,
                            'default_custom_field_type_id' => $field->default_custom_field_type_id,
                        ]);

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => ['The custom field id field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_deletes_a_field()
    {
        $user = $this->signin();

        $field = factory(Field::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->delete('/api/fields/'.$field->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('fields', [
            'id' => $field->id,
        ]);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $field->id,
        ]);
    }

    public function test_it_doesnt_delete_the_field_if_not_found()
    {
        $user = $this->signin();

        $response = $this->delete('/api/fields/2349273984279348');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_gets_a_single_field()
    {
        $user = $this->signin();

        $field = factory(Field::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/fields/'.$field->id);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureField,
        ]);
    }
}
