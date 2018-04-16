<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTypeGroupTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $relationshipType = factory('App\RelationshipType')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($relationshipType->account()->exists());
    }
}
