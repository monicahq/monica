<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_gets_the_right_number_of_relationship_types()
    {
        $user = $this->signin();

        $relationshipType = factory('App\RelationshipType', 10)->create([
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

        $relationshipTypeGroup = factory('App\RelationshipTypeGroup')->create([
            'account_id' => $user->account_id,
        ]);

        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $user->account_id,
            'name' => 'father',
            'name_reverse_relationship' => 'son',
            'relationship_type_group_id' => $relationshipTypeGroup->id,
            'delible' => 0,
        ]);
        $relationshipType2 = factory('App\RelationshipType')->create([
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

        $relationshipTypeGroup = factory('App\RelationshipTypeGroup')->create([
            'account_id' => $user->account_id,
        ]);

        $relationshipType = factory('App\RelationshipType')->create([
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
