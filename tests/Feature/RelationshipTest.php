<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Gender;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends FeatureTestCase
{
    use DatabaseTransactions, WithFaker;

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
}
