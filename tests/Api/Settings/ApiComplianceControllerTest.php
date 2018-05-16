<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Settings\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiComplianceControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureCompliance = [
        'id',
        'object',
        'term_version',
        'term_content',
        'privacy_version',
        'privacy_content',
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_terms()
    {
        $term = factory(Term::class, 10)->create([
            'term_version' => rand(1,100),
            'term_content' => 'dummy data',
            'privacy_version' => rand(1,100),
            'privacy_content' => 'dummy data',
        ]);

        $response = $this->json('GET', '/api/compliance/');

        $response->assertStatus(200);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
        ]);

        $response->assertJsonFragment([
            'id' => $contactField->id,
            'object' => 'contactfield',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCompliance,
        ]);
    }
}
