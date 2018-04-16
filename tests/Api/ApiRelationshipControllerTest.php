<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_rejects_the_api_call_if_parameters_are_not_right()
    {
        $user = $this->signin();
        $contactA = factory('App\Contact')->create([
            'account_id' => $user->account_id,
        ]);
        $contactB = factory('App\Contact')->create([
            'account_id' => $user->account_id,
        ]);

        // make sure contact_is is an integer
        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 'a',
                            'relationship_type_id' => 1,
                            'of_contact' => 1,
                        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => ['The contact is must be an integer.'],
            'error_code' => 41,
        ]);

        // make sure relationship type id is an integer
        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 1,
                            'relationship_type_id' => 'a',
                            'of_contact' => 1,
                        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => ['The relationship type id must be an integer.'],
            'error_code' => 41,
        ]);

        // make sure of_contact is an integer
        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 1,
                            'relationship_type_id' => 1,
                            'of_contact' => 'a',
                        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => ['The of contact must be an integer.'],
            'error_code' => 41,
        ]);
    }

    public function test_it_fails_if_relationship_type_id_is_invalid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 1,
                            'relationship_type_id' => 1,
                            'of_contact' => 1,
                        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'The resource has not been found.',
            'error_code' => 31,
        ]);
    }

    public function test_it_fails_if_contact_is_id_is_invalid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 1,
                            'relationship_type_id' => 1,
                            'of_contact' => 1,
                        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'The resource has not been found.',
            'error_code' => 31,
        ]);
    }

    public function test_it_fails_if_of_contact_id_is_invalid()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 1,
                            'relationship_type_id' => 1,
                            'of_contact' => 1,
                        ]);

        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'The resource has not been found.',
            'error_code' => 31,
        ]);
    }

    public function test_it_creates_a_new_resource()
    {
        $user = $this->signin();
        $contactA = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $contactB = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeB = factory('App\RelationshipType')->create([
            'account_id' => $user->account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => $contactB->id,
                        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('relationships', [
            'account_id' => auth()->user()->account_id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
            'relationship_type_id' => $relationshipType->id,
        ]);

        $this->assertDatabaseHas('relationships', [
            'account_id' => auth()->user()->account_id,
            'contact_is' => $contactB->id,
            'of_contact' => $contactA->id,
            'relationship_type_id' => auth()->user()->account->getRelationshipTypeByType($relationshipType->name_reverse_relationship)->id,
        ]);
    }

    public function test_it_displays_a_relationship()
    {
        $user = $this->signin();
        $contactA = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $contactB = factory('App\Contact')->create(['account_id' => $user->account->id]);
        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeB = factory('App\RelationshipType')->create([
            'account_id' => $user->account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $relationship = factory('App\Relationship')->create([
            'account_id' => $user->account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
        ]);

        $response = $this->json('GET', '/api/relationships/'.$relationship->id);

        $response->assertStatus(200)
                    ->assertJsonFragment([
                        'id' => $relationship->id,
                        'object' => 'relationship',
                    ]);;
    }
}
