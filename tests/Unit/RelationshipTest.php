<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Relationship\Relationship;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($relationship->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'contact_is' => $contact->id,
        ]);

        $this->assertTrue($relationship->contactIs()->exists());
    }

    public function test_it_belongs_to_another_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'of_contact' => $contact->id,
        ]);

        $this->assertTrue($relationship->ofContact()->exists());
    }

    public function test_it_belongs_to_a_relationship_type()
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

    public function test_it_belongs_to_a_contact_through_with_contact_field()
    {
        $contact = factory(Contact::class)->create([]);
        $relationship = factory(Relationship::class)->create([
            'of_contact' => $contact->id,
        ]);

        $this->assertTrue($relationship->ofContact()->exists());
    }
}
