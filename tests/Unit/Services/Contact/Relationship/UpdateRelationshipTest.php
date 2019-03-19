<?php

namespace Tests\Unit\Services\Contact\Relationship;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Relationship\UpdateRelationship;

class UpdateRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_relationship()
    {
        $account = factory(Account::class)->create();
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
        ];

        app(UpdateRelationship::class)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'id' => $relationship->id,
            'relationship_type_id' => $relationshipType->id,
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

    public function test_it_updates_a_relationship_and_reverse()
    {
        $account = factory(Account::class)->create();
        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipA = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipTypeA->id,
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);
        $relationshipB = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'contact_is' => $relationshipA->of_contact,
            'of_contact' => $relationshipA->contact_is,
            'relationship_type_id' => $relationshipTypeB->id,
        ]);

        $relationshipTypeC = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'child',
            'name_reverse_relationship' => 'parent',
        ]);
        $relationshipTypeD = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'parent',
            'name_reverse_relationship' => 'child',
        ]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationshipA->id,
            'relationship_type_id' => $relationshipTypeC->id,
        ];

        app(UpdateRelationship::class)->execute($request);

        $this->assertDatabaseHas('relationships', [
            'id' => $relationshipA->id,
            'relationship_type_id' => $relationshipTypeC->id,
        ]);
        $this->assertDatabaseHas('relationships', [
            'id' => $relationshipB->id,
            'relationship_type_id' => $relationshipTypeD->id,
        ]);
    }
}
