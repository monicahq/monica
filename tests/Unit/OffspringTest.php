<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OffspringTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_a_contact()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create([]);
        $offspring = factory('App\Offspring')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($offspring->contact()->exists());
    }
}
