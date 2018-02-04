<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $relationship = factory('App\Relationship')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($relationship->contact()->exists());
    }

    public function test_it_belongs_to_a_contact_through_with_contact_field()
    {
        $contact = factory('App\Contact')->create([]);
        $relationship = factory('App\Relationship')->create([
            'with_contact_id' => $contact->id,
        ]);

        $this->assertTrue($relationship->with_contact()->exists());
    }

    public function test_get_potential_partners_does_not_return_contacts_who_are_already_partner_with_the_contact()
    {
        $account = factory(\App\Account::class)->create();
        $franck = factory(\App\Contact::class)->create([
            'account_id' => $account->id,
        ]);

        // partner
        $john = factory(\App\Contact::class)->create([
            'id' => 2,
            'account_id' => $account->id,
            'is_partial' => 1,
        ]);

        $relationship = factory(\App\Relationship::class)->create([
            'account_id' => $account->id,
            'contact_id' => $franck->id,
            'with_contact_id' => $john->id,
        ]);

        // additional contacts
        $jane = factory(\App\Contact::class)->create([
            'id' => 3,
            'account_id' => $account->id,
        ]);
        $marie = factory(\App\Contact::class)->create([
            'id' => 4,
            'account_id' => $account->id,
        ]);

        $this->assertEquals(
            2,
            $franck->getPotentialContacts()->count()
        );
    }

    public function test_set_partner_sets_a_relationship_between_the_two_contacts()
    {
        // unilateral relationship - this is when the SO is not a real Contact object
        $john = factory(\App\Contact::class)->create([
            'id' => 1,
        ]);
        $roger = factory(\App\Contact::class)->create([
            'id' => 2,
        ]);

        $john->setRelationshipWith($roger);

        $this->assertDatabaseHas('relationships', [
            'contact_id' => $john->id,
            'with_contact_id' => $roger->id,
        ]);

        $this->assertDatabaseMissing('relationships', [
            'contact_id' => $roger->id,
            'with_contact_id' => $john->id,
        ]);

        $john->unsetRelationshipWith($roger);

        $this->assertDatabaseMissing('relationships', [
            'contact_id' => $john->id,
            'with_contact_id' => $roger->id,
        ]);

        // now testing the bilateral relationship

        $marie = factory(\App\Contact::class)->create([
            'id' => 3,
        ]);
        $stephanie = factory(\App\Contact::class)->create([
            'id' => 4,
        ]);

        $marie->setRelationshipWith($stephanie, true);

        $this->assertDatabaseHas('relationships', [
            'contact_id' => $marie->id,
            'with_contact_id' => $stephanie->id,
        ]);

        $this->assertDatabaseHas('relationships', [
            'contact_id' => $stephanie->id,
            'with_contact_id' => $marie->id,
        ]);

        $marie->unsetRelationshipWith($stephanie, true);

        $this->assertDatabaseMissing('relationships', [
            'contact_id' => $marie->id,
            'with_contact_id' => $stephanie->id,
        ]);

        $this->assertDatabaseMissing('relationships', [
            'contact_id' => $stephanie->id,
            'with_contact_id' => $marie->id,
        ]);
    }
}
