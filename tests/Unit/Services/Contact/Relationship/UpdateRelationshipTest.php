<?php

namespace Tests\Unit\Services\Contact\Relationship;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\Relationship\CreateRelationship;
use App\Services\Contact\Relationship\UpdateRelationship;

class UpdateRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_relationship()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $otherContact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType0 = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'son',
            'name_reverse_relationship' => 'father',
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => $relationshipType0->name_reverse_relationship,
            'name_reverse_relationship' => $relationshipType0->name,
        ]);
        $request = [
            'contact_id' => $contact->id,
            'other_contact_id' => $otherContact->id,
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $relationship = app(CreateRelationship::class)->execute($request);

        $relationshipType0 = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => $relationshipType0->name_reverse_relationship,
            'name_reverse_relationship' => $relationshipType0->name,
        ]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $newRelationship = app(UpdateRelationship::class)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'id' => $newRelationship->id,
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $otherContact->id,
        ]);
    }

    public function test_it_updates_a_partial_relationship()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $otherContact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'contact_is' => $contact->id,
            'of_contact' => $otherContact->id,
        ]);

        $relationshipType0 = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'name',
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name_reverse_relationship' => $relationshipType0->name,
        ]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $newRelationship = app(UpdateRelationship::class)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'id' => $newRelationship->id,
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
            'contact_is' => $contact->id,
            'of_contact' => $otherContact->id,
        ]);
    }
}
