<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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
            'term_version' => rand(1, 100),
            'term_content' => 'dummy data',
            'privacy_version' => rand(1, 100),
            'privacy_content' => 'dummy data',
        ]);

        $response = $this->json('GET', '/api/compliance/');

        $response->assertStatus(200);

        $this->assertCount(
            Term::get()->count(),
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => Term::get()->count(),
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructureCompliance,
            ],
        ]);
    }

    public function test_it_gets_a_single_term()
    {
        $term = factory(Term::class)->create([
            'term_version' => rand(1, 100),
            'term_content' => 'dummy data',
            'privacy_version' => rand(1, 100),
            'privacy_content' => 'dummy data',
        ]);

        $response = $this->json('GET', '/api/compliance/'.$term->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $term->id,
            'object' => 'term',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureCompliance,
        ]);
    }

    public function test_it_doesnt_get_a_single_term()
    {
        $response = $this->json('GET', '/api/compliance/3');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }
}
