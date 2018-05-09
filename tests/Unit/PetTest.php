<?php

namespace Tests\Unit;

use App\Pet;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $pet = factory('App\Pet')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($pet->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $pet = factory('App\Pet')->create([
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($pet->contact()->exists());
    }

    public function test_it_belongs_to_a_pet_category()
    {
        $petCategory = factory('App\PetCategory')->create([]);
        $pet = factory('App\Pet')->create([
            'pet_category_id' => $petCategory->id,
        ]);

        $this->assertTrue($pet->petCategory()->exists());
    }

    public function test_it_sets_name()
    {
        $pet = new Pet;
        $this->assertNull($pet->name);

        $pet->name = 'henri';
        $this->assertEquals(
            'henri',
            $pet->name
        );
    }
}
