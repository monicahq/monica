<?php

namespace Tests\Unit\Services\Contact\Relationship;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Relationship\DestroyRelationship;

class DestroyRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_relationship()
    {
        $contactA = factory(Contact::class)->create([]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $contactA->account_id,
        ]);

        $relationship = factory(Relationship::class)->create([
            'account_id' => $contactA->account_id,
            'contact_is' => $contactA,
            'of_contact' => $contactB,
        ]);

        $request = [
            'account_id' => $contactA->account_id,
            'relationship_id' => $relationship->id,
        ];

        app(DestroyRelationship::class)->execute($request);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationship->id,
        ]);
    }

    public function test_it_destroys_a_relationship_and_reverse()
    {
        $contactA = factory(Contact::class)->create([]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $contactA->account_id,
        ]);

        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $contactA->account_id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipA = factory(Relationship::class)->create([
            'account_id' => $contactA->account_id,
            'contact_is' => $contactA,
            'of_contact' => $contactB,
            'relationship_type_id' => $relationshipTypeA->id,
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $contactA->account_id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);
        $relationshipB = factory(Relationship::class)->create([
            'account_id' => $contactA->account_id,
            'contact_is' => $contactB,
            'of_contact' => $contactA,
            'relationship_type_id' => $relationshipTypeB->id,
        ]);

        $request = [
            'account_id' => $contactA->account_id,
            'relationship_id' => $relationshipA->id,
        ];

        $this->assertDatabaseHas('relationships', [
            'id' => $relationshipA->id,
        ]);
        $this->assertDatabaseHas('relationships', [
            'id' => $relationshipB->id,
        ]);

        app(DestroyRelationship::class)->execute($request);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationshipA->id,
        ]);
        $this->assertDatabaseMissing('relationships', [
            'id' => $relationshipB->id,
        ]);
    }

    public function test_it_destroys_a_relationship_and_reverse_and_partial_contact()
    {
        $contactA = factory(Contact::class)->create([]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $contactA->account_id,
            'is_partial' => true,
        ]);

        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $contactA->account_id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipA = factory(Relationship::class)->create([
            'account_id' => $contactA->account_id,
            'contact_is' => $contactA,
            'of_contact' => $contactB,
            'relationship_type_id' => $relationshipTypeA->id,
        ]);

        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $contactA->account_id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);
        $relationshipB = factory(Relationship::class)->create([
            'account_id' => $contactA->account_id,
            'contact_is' => $contactB,
            'of_contact' => $contactA,
            'relationship_type_id' => $relationshipTypeB->id,
        ]);

        $request = [
            'account_id' => $contactA->account_id,
            'relationship_id' => $relationshipA->id,
        ];

        $this->assertDatabaseHas('relationships', [
            'id' => $relationshipA->id,
        ]);
        $this->assertDatabaseHas('relationships', [
            'id' => $relationshipB->id,
        ]);

        app(DestroyRelationship::class)->execute($request);

        $this->assertDatabaseMissing('relationships', [
            'id' => $relationshipA->id,
        ]);
        $this->assertDatabaseMissing('relationships', [
            'id' => $relationshipB->id,
        ]);
        $this->assertDatabaseMissing('contacts', [
            'id' => $contactB->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyRelationship::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_relationship_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create();
        $relationship = factory(Relationship::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
        ];

        $this->expectException(ModelNotFoundException::class);

        app(DestroyRelationship::class)->execute($request);
    }

    public function test_it_deletes_relationship_between_two_contacts_and_deletes_the_contact()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $partner = factory(Contact::class)->create([
            'account_id' => $account->id,
            'is_partial' => true,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
            'relationship_type_id' => $relationshipType->id,
        ]);

        app(DestroyRelationship::class)->execute([
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
        ]);

        $this->assertDatabaseMissing(
            'relationships',
            [
                'contact_is' => $contact->id,
                'of_contact' => $partner->id,
                'relationship_type_id' => $relationshipType->id,
            ]
        );
    }

    public function test_it_deletes_relationship_between_two_contacts_and_doesnt_delete_the_contact()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $partner = factory(Contact::class)->create([
            'account_id' => $account->id,
            'is_partial' => false,
        ]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'contact_is' => $contact->id,
            'of_contact' => $partner->id,
            'relationship_type_id' => $relationshipType->id,
        ]);

        app(DestroyRelationship::class)->execute([
            'account_id' => $account->id,
            'relationship_id' => $relationship->id,
        ]);

        $this->assertDatabaseMissing(
            'relationships',
            [
                'contact_is' => $contact->id,
                'of_contact' => $partner->id,
                'relationship_type_id' => $relationshipType->id,
            ]
        );

        $this->assertDatabaseHas(
            'contacts',
            [
                'id' => $partner->id,
            ]
        );
    }
}
