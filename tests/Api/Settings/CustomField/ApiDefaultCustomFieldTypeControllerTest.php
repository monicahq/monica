<?php

namespace Tests\Api\Setings\CustomField;

use Tests\ApiTestCase;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\DefaultCustomFieldType;

class ApiDefaultCustomFieldTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureDefaultCustomFieldType = [
        'id',
        'object',
        'type',
    ];

    public function test_it_gets_a_list_of_custom_field_types()
    {
        $user = $this->signin();

        $defaultCustomFieldTypes = factory(DefaultCustomFieldType::class, 10)->create([]);

        $response = $this->json('GET', '/api/defaultcustomfieldtypes');

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureDefaultCustomFieldType,
            ],
        ]);
    }

    public function test_it_applies_limit_parameter()
    {
        $user = $this->signin();

        $defaultCustomFieldTypes = factory(DefaultCustomFieldType::class, 10)->create([]);

        $response = $this->json('GET', '/api/defaultcustomfieldtypes?limit=1');

        $response->assertJsonFragment([
            'total' => 13, // 13 because 3 exist by default in the database
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 13,
        ]);

        $response = $this->json('GET', '/api/defaultcustomfieldtypes?limit=2');

        $response->assertJsonFragment([
            'total' => 13,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 7,
        ]);
    }

    public function test_it_gets_a_single_default_custom_field_type()
    {
        $user = $this->signin();

        $defaultCustomFieldType = factory(DefaultCustomFieldType::class)->create([]);

        $response = $this->json('GET', '/api/defaultcustomfieldtypes/'.$defaultCustomFieldType->id);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureDefaultCustomFieldType,
        ]);
    }
}
