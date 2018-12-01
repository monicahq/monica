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
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_gets_the_right_number_of_relationship_types()
    {
        $user = $this->signin();

        $relationshipType = factory(RelationshipType::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/relationshiptypes');

        $response->assertStatus(200);
        $decodedJson = $response->decodeResponseJson();

        $this->assertCount(
            10,
            $decodedJson['data']
        );
    }

    public function test_it_gets_the_list_of_relationship_types()
    {
        $user = $this->signin();

        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $user->account_id,
        ]);

        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'father',
            'name_reverse_relationship' => 'son',
            'relationship_type_group_id' => $relationshipTypeGroup->id,
            'delible' => 0,
        ]);
        $relationshipType2 = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'son',
            'name_reverse_relationship' => 'father',
            'relationship_type_group_id' => $relationshipTypeGroup->id,
            'delible' => 0,
        ]);

        $response = $this->json('GET', '/api/relationshiptypes');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $relationshipType2->id,
            'object' => 'relationshiptype',
            'name' => 'son',
            'delible' => false,
        ]);
    }

    public function test_it_gets_a_specific_relationship_type_group()
    {
        $user = $this->signin();

        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $user->account_id,
        ]);

        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'father',
            'name_reverse_relationship' => 'son',
            'relationship_type_group_id' => $relationshipTypeGroup->id,
            'delible' => 0,
        ]);

        $response = $this->json('GET', '/api/relationshiptypes/'.$relationshipType->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $relationshipType->id,
            'object' => 'relationshiptype',
            'name' => 'father',
            'name_reverse_relationship' => 'son',
            'relationship_type_group_id' => $relationshipTypeGroup->id,
            'delible' => false,
        ]);
    }
}
