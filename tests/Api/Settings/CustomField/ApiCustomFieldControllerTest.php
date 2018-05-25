<?php

namespace Tests\Api\Setings\CustomField;

use Tests\ApiTestCase;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\CustomFieldPattern;

class ApiCustomFieldControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureCustomField = [
        'id',
        'object',
        'name',
        'fields_order',
        'is_list',
        'is_important',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_custom_fields()
    {
        $user = $this->signin();

        $customFields = factory(CustomField::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/customfields');

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureCustomField,
            ],
        ]);
    }

    public function test_it_applies_limit_parameter()
    {
        $user = $this->signin();

        $customFields = factory(CustomField::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/customfields?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/customfields?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_stores_a_custom_field()
    {
        $user = $this->signin();

        $customFieldPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/customfields', [
                            'name' => 'Movies',
                            'is_list' => true,
                            'is_important' => false,
                            'custom_field_pattern_id' => $customFieldPattern->id,
                        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('custom_fields', [
            'name' => 'Movies',
            'is_list' => 1,
            'is_important' => 0,
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCustomField,
        ]);
    }

    public function test_it_doesnt_store_a_custom_field_if_query_not_valid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/customfields', [
                            'name' => 'Movies',
                            'is_list' => false,
                        ]);

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => ['The is important field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_updates_a_custom_field()
    {
        $user = $this->signin();

        $customField = factory(CustomField::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
            'is_list' => 0,
            'is_important' => 0,
        ]);

        $response = $this->json('PUT', '/api/customfields/'.$customField->id, [
                            'name' => 'Movies',
                            'is_list' => true,
                            'is_important' => true,
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('custom_fields', [
            'name' => 'Movies',
            'is_list' => 1,
            'is_important' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCustomField,
        ]);
    }

    public function test_it_doesnt_update_if_custom_field_not_found()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/customfields/2349273984279348', [
                            'name' => 'Movies',
                            'is_list' => false,
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

        $customField = factory(CustomField::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
            'is_list' => 0,
            'is_important' => 0,
        ]);

        $response = $this->json('PUT', '/api/customfields/'.$customField->id, [
                            'name' => 'Movies',
                            'is_list' => true,
                        ]);

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => ['The is important field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_deletes_a_custom_field()
    {
        $user = $this->signin();

        $customField = factory(CustomField::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
            'is_list' => 0,
            'is_important' => 0,
        ]);

        $response = $this->delete('/api/customfields/'.$customField->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('custom_fields', [
            'id' => $customField->id,
        ]);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $customField->id,
        ]);
    }

    public function test_it_doesnt_delete_the_custom_field_if_not_found()
    {
        $user = $this->signin();

        $response = $this->delete('/api/customfields/2349273984279348');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_gets_a_single_custom_field()
    {
        $user = $this->signin();

        $customField = factory(CustomField::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/customfields/'.$customField->id);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCustomField,
        ]);
    }
}
