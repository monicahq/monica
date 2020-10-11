<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Relationship\RelationshipType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RelationshipTypeGroupTest extends TestCase
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
}
