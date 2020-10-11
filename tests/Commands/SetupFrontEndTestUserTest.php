<?php

namespace Tests\Commands;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SetupFrontEndTestUserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_create_a_test_user()
    {
        $this->withoutMockingConsoleOutput();

        $accountCount = Account::count();
        $userCount = User::count();

        $exitCode = $this->artisan('setup:frontendtestuser');

        $this->assertEquals($accountCount + 1, Account::count());
        $this->assertEquals($userCount + 1, User::count());
    }
}
