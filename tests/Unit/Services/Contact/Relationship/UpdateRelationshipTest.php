<?php

namespace Tests\Unit\Services\Contact\Relationship;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            'contact_is' => $contact->id,
            'of_contact' => $otherContact->id,
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

    public function test_it_throws_an_exception_if_relationship_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $relationship = factory(Relationship::class)->create();
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(UpdateRelationship::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_relationship_type_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create();

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(UpdateRelationship::class)->execute($request);
    }
}
