<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_rejects_the_api_call_if_parameters_are_not_right()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
        ]);

        // make sure contact_is is an integer
        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 'a',
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => $contactB->id,
                        ]);

        $this->expectDataError($response, ['The contact is must be an integer.']);

        // make sure relationship type id is an integer
        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => 'a',
                            'of_contact' => $contactB->id,
                        ]);

        $this->expectDataError($response, ['The relationship type id must be an integer.']);

        // make sure of_contact is an integer
        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => 'a',
                        ]);

        $this->expectDataError($response, ['The of contact must be an integer.']);
    }

    public function test_it_fails_if_relationship_type_id_is_invalid()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create();

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => $contactB->id,
                        ]);

        $this->expectNotFound($response);
    }

    public function test_it_fails_if_contact_is_id_is_invalid()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create();
        $contactB = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => $contactB->id,
                        ]);

        $this->expectNotFound($response);
    }

    public function test_it_fails_if_of_contact_id_is_invalid()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactB = factory(Contact::class)->create();
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => $contactB->id,
                        ]);

        $this->expectNotFound($response);
    }

    public function test_it_creates_a_new_resource()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $contactB = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => $contactA->id,
                            'relationship_type_id' => $relationshipType->id,
                            'of_contact' => $contactB->id,
                        ]);

        $response->assertStatus(201);

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
        $contactA = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $contactB = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $relationship = factory(Relationship::class)->create([
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
                    ]);
    }

    public function test_it_deletes_a_relationship()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $contactB = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
        ]);

        $relationshipB = factory(Relationship::class)->create([
            'account_id' => $user->account->id,
            'relationship_type_id' => $relationshipTypeB->id,
            'contact_is' => $contactB->id,
            'of_contact' => $contactA->id,
        ]);

        $response = $this->json('DELETE', '/api/relationships/'.$relationship->id);

        $response->assertStatus(200)
                    ->assertJson([
                        'deleted' => true,
                        'id' => $relationship->id,
                    ]);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationship->id,
        ]);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationshipB->id,
        ]);
    }

    public function test_it_rejects_the_delete_api_call_if_parameters_are_not_right()
    {
        $user = $this->signin();

        // make sure relationship id is valid
        $response = $this->json('DELETE', '/api/relationships/0');
        $this->expectDataError($response, ['The selected relationship id is invalid.']);

        // make sure relationship id is an integer
        $response = $this->json('DELETE', '/api/relationships/x');
        $this->expectDataError($response, ['The relationship id must be an integer.']);

        // make sure relationship id is with the right account
        $relationship = factory(Relationship::class)->create();
        $response = $this->json('DELETE', '/api/relationships/'.$relationship->id);
        $this->expectNotFound($response);
    }

    public function test_it_rejects_the_update_api_call_if_parameters_are_not_right()
    {
        $user = $this->signin();
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create();

        // make sure relationship type id is an integer
        $response = $this->json('PUT', '/api/relationships/'.$relationship->id, [
                            'relationship_type_id' => $relationshipType->id,
                        ]);

        $this->expectNotFound($response);
    }

    public function test_it_rejects_the_update_api_call_if_parameters_are_not_right2()
    {
        $user = $this->signin();
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account->id,
        ]);

        // make sure relationship type id is an integer
        $response = $this->json('PUT', '/api/relationships/'.$relationship->id, [
                            'relationship_type_id' => 'a',
                        ]);

        $this->expectDataError($response, ['The relationship type id must be an integer.']);
    }

    public function test_it_fails_the_update_if_relationship_type_id_is_invalid()
    {
        $user = $this->signin();
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create();

        $response = $this->json('PUT', '/api/relationships/'.$relationship->id, [
                            'relationship_type_id' => $relationshipType->id,
                        ]);

        $this->expectNotFound($response);
    }

    public function test_it_updates_a_relationship()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $contactB = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeC = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'fuckfriend',
            'name_reverse_relationship' => 'funnysituation',
        ]);

        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
        ]);

        $response = $this->json('PUT', '/api/relationships/'.$relationship->id, [
            'relationship_type_id' => $relationshipTypeC->id,
        ]);

        $response->assertStatus(200)
                    ->assertJsonFragment([
                        'id' => $relationshipTypeC->id,
                        'name' => 'fuckfriend',
                    ]);

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'relationship_type_id' => $relationshipTypeC->id,
        ]);
    }

    public function test_it_displays_all_relationships_of_a_contact()
    {
        $user = $this->signin();
        $contactA = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $contactB = factory(Contact::class)->create(['account_id' => $user->account->id]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $user->account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $relationship = factory(Relationship::class, 3)->create([
            'account_id' => $user->account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contactA->id.'/relationships');

        $response->assertStatus(200);

        $decodedJson = $response->decodeResponseJson();

        $this->assertCount(
            3,
            $decodedJson['data']
        );
    }
}
