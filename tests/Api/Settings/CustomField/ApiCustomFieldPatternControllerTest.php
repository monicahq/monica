<?php

namespace Tests\Api\Setings\CustomField;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\CustomFieldPattern;

class ApiCustomFieldPatternControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureCustomFieldPattern = [
        'id',
        'object',
        'name',
        'icon_name',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_contact_fields()
    {
        $user = $this->signin();

        $customFieldPatterns = factory(CustomFieldPattern::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/customfieldpatterns');

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureCustomFieldPattern,
            ],
        ]);
    }

    public function test_it_applies_limit_parameter()
    {
        $user = $this->signin();

        $customFieldPatterns = factory(CustomFieldPattern::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/customfieldpatterns?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/customfieldpatterns?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_stores_a_custom_field_pattern()
    {
        $user = $this->signin();

        $customFieldPatternPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/customfieldpatterns', [
                            'name' => 'Friends and family',
                            'icon_name' => 'random name',
                        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('custom_field_patterns', [
            'name' => 'Friends and family',
            'icon_name' => 'random name',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCustomFieldPattern,
        ]);
    }

    public function test_it_doesnt_store_a_custom_field_pattern_if_query_not_valid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/customfieldpatterns', [
                            'name' => 'Friends and family',
                        ]);

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => ['The icon name field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_updates_a_custom_field_pattern()
    {
        $user = $this->signin();

        $customFieldPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
            'icon_name' => 'random name',
        ]);

        $response = $this->json('PUT', '/api/customfieldpatterns/'.$customFieldPattern->id, [
                            'name' => 'Friends and family',
                            'icon_name' => 'random name',
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('custom_field_patterns', [
            'name' => 'Friends and family',
            'icon_name' => 'random name',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCustomFieldPattern,
        ]);
    }

    public function test_it_doesnt_update_if_custom_field_not_found()
    {
        $user = $this->signin();

        $response = $this->json('PUT', '/api/customfieldpatterns/2349273984279348', [
                            'name' => 'Friends and family',
                            'icon_name' => 'random name',
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

        $customFieldPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
            'icon_name' => 'random name',
        ]);

        $response = $this->json('PUT', '/api/customfieldpatterns/'.$customFieldPattern->id, [
                            'name' => 'Friends and family',
                        ]);

        $response->assertStatus(400);

        $response->assertJsonFragment([
            'message' => ['The icon name field is required.'],
            'error_code' => 32,
        ]);
    }

    public function test_it_deletes_a_custom_field_pattern()
    {
        $user = $this->signin();

        $customFieldPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $user->account_id,
            'name' => 'France',
            'icon_name' => 'random name',
        ]);

        $response = $this->delete('/api/customfieldpatterns/'.$customFieldPattern->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('custom_field_patterns', [
            'id' => $customFieldPattern->id,
        ]);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $customFieldPattern->id,
        ]);
    }

    public function test_it_doesnt_delete_the_custom_field_pattern_if_not_found()
    {
        $user = $this->signin();

        $response = $this->delete('/api/customfieldpatterns/2349273984279348');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_gets_a_single_custom_field_pattern()
    {
        $user = $this->signin();

        $customFieldPattern = factory(CustomFieldPattern::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/customfieldpatterns/'.$customFieldPattern->id);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCustomFieldPattern,
        ]);
    }
}
