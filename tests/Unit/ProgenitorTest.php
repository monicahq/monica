<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProgenitorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $progenitor = factory('App\Progenitor')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($progenitor->contact()->exists());
    }
}
