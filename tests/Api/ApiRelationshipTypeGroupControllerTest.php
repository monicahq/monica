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



namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipTypeGroupControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_gets_the_right_number_of_relationship_type_groups()
    {
        $user = $this->signin();

        $relationshipTypeGroup = factory(RelationshipTypeGroup::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/relationshiptypegroups');

        $response->assertStatus(200);
        $decodedJson = $response->decodeResponseJson();

        $this->assertCount(
            10,
            $decodedJson['data']
        );
    }

    public function test_it_gets_the_list_of_relationship_type_groups()
    {
        $user = $this->signin();

        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $user->account_id,
            'name' => 'love',
            'delible' => 0,
        ]);
        $relationshipTypeGroup2 = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $user->account_id,
            'name' => 'hate',
            'delible' => 0,
        ]);

        $response = $this->json('GET', '/api/relationshiptypegroups');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $relationshipTypeGroup2->id,
            'object' => 'relationshiptypegroup',
            'name' => 'hate',
            'delible' => false,
        ]);
    }

    public function test_it_gets_a_specific_relationship_type_group()
    {
        $user = $this->signin();

        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $user->account_id,
            'name' => 'love',
            'delible' => 0,
        ]);

        $response = $this->json('GET', '/api/relationshiptypegroups/'.$relationshipTypeGroup->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $relationshipTypeGroup->id,
            'object' => 'relationshiptypegroup',
            'name' => 'love',
            'delible' => false,
        ]);
    }
}
