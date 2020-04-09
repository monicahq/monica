<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($relationship->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'contact_is' => $contact->id,
        ]);

        $this->assertTrue($relationship->contactIs()->exists());
    }

    /** @test */
    public function it_belongs_to_another_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'of_contact' => $contact->id,
        ]);

        $this->assertTrue($relationship->ofContact()->exists());
    }

    /** @test */
    public function it_belongs_to_a_relationship_type()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipType->id,
        ]);

        $this->assertTrue($relationship->relationshipType()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact_through_with_contact_field()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'of_contact' => $contact->id,
        ]);

        $this->assertTrue($relationship->ofContact()->exists());
    }

    /** @test */
    public function it_gets_the_reverse_relationship()
    {
        $account = factory(Account::class)->create();
        $contactA = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipA = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipTypeA->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
        ]);
        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);
        $relationshipB = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipTypeB->id,
            'contact_is' => $contactB->id,
            'of_contact' => $contactA->id,
        ]);

        $reverseRelationship = $relationshipA->reverseRelationship();
        $this->assertEquals(
            $relationshipB->id,
            $reverseRelationship->id
        );

        $reverseReverseRelationship = $reverseRelationship->reverseRelationship();
        $this->assertEquals(
            $relationshipA->id,
            $reverseReverseRelationship->id
        );
    }

    /** @test */
    public function it_not_gets_the_reverse_relationship()
    {
        $account = factory(Account::class)->create();
        $contactA = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
        ]);
        $relationshipA = factory(Relationship::class)->create([
            'account_id' => $account->id,
            'relationship_type_id' => $relationshipTypeA->id,
            'contact_is' => $contactA->id,
            'of_contact' => $contactB->id,
        ]);

        $reverseRelationship = $relationshipA->reverseRelationship();

        $this->assertNull($reverseRelationship);
    }
}
