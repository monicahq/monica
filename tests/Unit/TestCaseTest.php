<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestCaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_user_in_an_account(): void
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);

        $assigned = $this->assignUserToAccount(user: $user, account: $account);

        $this->assertEquals($account->id, $assigned->account_id);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'account_id' => $account->id,
        ]);
    }

    #[Test]
    public function it_takes_the_user_out_of_the_account_they_were_in(): void
    {
        $user = User::factory()->create(['first_name' => 'Dwight', 'last_name' => 'Schrute']);
        $previousAccountId = $user->account_id;
        $account = Account::factory()->create();

        $this->assignUserToAccount(user: $user, account: $account);

        $this->assertDatabaseMissing('users', ['account_id' => $previousAccountId]);
    }
}
