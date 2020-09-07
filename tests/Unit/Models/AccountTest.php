<?php

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\Information;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_many_users()
    {
        $account = factory(Account::class)->create();
        factory(User::class, 2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->users()->exists());
    }
}
