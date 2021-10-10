<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_right_number_of_relationship_types()
    {
        $user = $this->signin();

        factory(RelationshipType::class, 10)->create([
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

    /** @test */
    public function it_gets_the_list_of_relationship_types()
    {
        $user = $this->signin();

        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $user->account_id,
        ]);

        factory(RelationshipType::class)->create([
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

    /** @test */
    public function it_gets_a_specific_relationship_type_group()
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
