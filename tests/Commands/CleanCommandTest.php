<?php

namespace Tests\Commands;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\SyncToken;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CleanCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function clean_command_left_one_token()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now(),
        ]);

        $this->artisan('monica:clean')->run();

        $this->assertDatabaseHas('synctoken', [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ]);
    }

    /** @test */
    public function clean_command_left_all_token()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $s1 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now(),
        ]);
        $s2 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now()->addDays(-10),
        ]);

        $command = $this->artisan('monica:clean');
        $command->expectsOutput("Delete token {$s2->id} - User {$user->id} - Type contacts - timestamp {$s2->timestamp}");
        $command->run();

        $this->assertDatabaseHas('synctoken', [
            'id' => $s1->id,
        ]);
        $this->assertDatabaseMissing('synctoken', [
            'id' => $s2->id,
        ]);
    }

    /** @test */
    public function clean_command_dryrun()
    {
        $account = factory(Account::class)->create();
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $s1 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now(),
        ]);
        $s2 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now()->addDays(-10),
        ]);

        $this->artisan('monica:clean --dry-run')->run();

        $this->assertDatabaseHas('synctoken', [
            'id' => $s1->id,
        ]);
        $this->assertDatabaseHas('synctoken', [
            'id' => $s2->id,
        ]);
    }
}
