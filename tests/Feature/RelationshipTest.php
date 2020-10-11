<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends FeatureTestCase
{
    use DatabaseTransactions, WithFaker;

    public function test_create_a_relationship()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get('/people/'.$contact->hashID().'/relationships/create');

        $response->assertStatus(200);

        $response->assertSee('This person is...');
    }

    public function test_user_can_add_a_relationship()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $partner = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'relationship_type' => 'existing',
            'existing_contact_id' => $partner->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/relationships', $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('relationships', [
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_user_can_add_a_relationship_new_user_birthdate_unknown()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'relationship_type' => 'new',
            'relationship_type_id' => $relationshipType->id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $gender->id,
            'birthdate' => 'unknown',
            'realContact' => true,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/relationships', $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $gender->id,
            'is_partial' => false,
        ]);
        $this->assertDatabaseHas('relationships', [
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_user_can_add_a_relationship_new_user_partial()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'relationship_type' => 'new',
            'relationship_type_id' => $relationshipType->id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $gender->id,
            'birthdate' => 'unknown',
            'realContact' => false,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/relationships', $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $gender->id,
            'is_partial' => true,
        ]);
        $this->assertDatabaseHas('relationships', [
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_user_can_add_a_relationship_new_user_birthdate_known()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'relationship_type' => 'new',
            'relationship_type_id' => $relationshipType->id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $gender->id,
            'birthdate' => 'exact',
            'birthdayDate' => '1947-07-30',
            'realContact' => true,
        ];

        $response = $this->post('/people/'.$contact->hashID().'/relationships', $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $gender->id,
            'is_partial' => false,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account_id,
            'date' => '1947-07-30',
            'is_age_based' => false,
            'is_year_unknown' => false,
        ]);
        $this->assertDatabaseHas('relationships', [
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_edit_a_relationship()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $partner = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'Homer',
            'last_name' => 'Simpson',
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
        ]);

        $response = $this->get('/people/'.$contact->hashID().'/relationships/'.$relationship->id.'/edit');

        $response->assertStatus(200);

        $response->assertSee('Homer Simpson is...');
    }

    public function test_user_can_update_a_relationship()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $partner = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $response = $this->put('/people/'.$contact->hashID().'/relationships/'.$relationship->id, $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_user_can_update_a_relationship_partial_user()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $partner = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'is_partial' => true,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $params = [
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'gender_id' => $partner->gender_id,
            'birthdate' => 'exact',
            'birthdayDate' => '1947-07-30',
        ];

        $response = $this->put('/people/'.$contact->hashID().'/relationships/'.$relationship->id, $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('contacts', [
            'account_id' => $user->account_id,
            'first_name' => 'Arnold',
            'last_name' => 'Schwarzenegger',
            'is_partial' => true,
        ]);
        $this->assertDatabaseHas('special_dates', [
            'account_id' => $user->account_id,
            'date' => '1947-07-30',
            'is_age_based' => false,
            'is_year_unknown' => false,
        ]);
        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
            'relationship_type_id' => $relationshipType->id,
        ]);
    }

    public function test_user_can_destroy_a_relationship()
    {
        $user = $this->signIn();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $partner = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
        ]);

        $response = $this->delete('/people/'.$contact->hashID().'/relationships/'.$relationship->id);

        $response->assertStatus(302);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationship->id,
            'account_id' => $user->account_id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
        ]);
    }
}
