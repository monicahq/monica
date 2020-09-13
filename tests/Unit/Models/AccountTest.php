<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_many_users()
    {
        $account = Account::factory()->create();
        User::factory()->count(2)->create([
            'account_id' => $account->id,
        ]);

        Assert::assertTrue($account->users()->exists());
    }
}
