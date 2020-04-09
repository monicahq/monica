<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($relationshipType->account()->exists());
    }

    /** @test */
    public function it_belongs_to_an_relationship_type_group()
    {
        $account = factory(Account::class)->create([]);
        $relationshipTypeGroup = factory(RelationshipTypeGroup::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($relationshipTypeGroup->account()->exists());
    }

    /** @test */
    public function it_gets_the_masculine_short_name_of_the_relationship_type()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $this->assertEquals(
            'uncle',
            $relationshipType->getLocalizedName()
        );
    }

    /** @test */
    public function it_gets_the_feminine_short_name_of_the_relationship_type()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);

        $this->assertEquals(
            'aunt',
            $relationshipType->getLocalizedName(null, false, 'F')
        );
    }

    /** @test */
    public function it_gets_the_masculine_name_of_the_relationship_type_with_the_name_of_the_contact()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'first_name' => 'Mark',
            'last_name' => 'Twain',
        ]);

        $this->assertEquals(
            'Mark Twain’s uncle',
            $relationshipType->getLocalizedName($contact, false, 'M')
        );
    }

    /** @test */
    public function it_gets_the_feminine_name_of_the_relationship_type_with_the_name_of_the_contact()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'first_name' => 'Mark',
            'last_name' => 'Twain',
        ]);

        $this->assertEquals(
            'Mark Twain’s aunt',
            $relationshipType->getLocalizedName($contact, false, 'F')
        );
    }

    /** @test */
    public function it_gets_both_names_of_the_relationship_type_with_the_name_of_the_contact_and_the_opposite_version()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'first_name' => 'Mark',
            'last_name' => 'Twain',
        ]);

        $this->assertEquals(
            'Mark Twain’s uncle/aunt',
            $relationshipType->getLocalizedName($contact, true)
        );
    }

    /** @test */
    public function it_gets_only_one_name_of_the_relationship_type_if_name_and_name_reverse_are_similar()
    {
        $account = factory(Account::class)->create([]);
        $relationshipType = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'partner',
            'name_reverse_relationship' => 'partner',
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'first_name' => 'Mark',
            'last_name' => 'Twain',
        ]);

        $this->assertEquals(
            'Mark Twain’s significant other',
            $relationshipType->getLocalizedName($contact, true)
        );
    }

    /** @test */
    public function it_gets_the_reverse_relationship_type()
    {
        $account = factory(Account::class)->create([]);
        $relationshipTypeA = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'uncle',
            'name_reverse_relationship' => 'nephew',
        ]);
        $relationshipTypeB = factory(RelationshipType::class)->create([
            'account_id' => $account->id,
            'name' => 'nephew',
            'name_reverse_relationship' => 'uncle',
        ]);

        $reverseRelationshipType = $relationshipTypeA->reverseRelationshipType();

        $this->assertEquals(
            $relationshipTypeB->id,
            $reverseRelationshipType->id
        );

        $reverseReverseRelationshipType = $reverseRelationshipType->reverseRelationshipType();
        $this->assertEquals(
            $relationshipTypeA->id,
            $reverseReverseRelationshipType->id
        );
    }
}
