<?php

namespace Tests\Unit\Services\VCard;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\SyncToken;
use App\Models\Account\Account;
use Sabre\VObject\PHPUnitAssertions;
use App\Services\Instance\TokenClean;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TokenCleanTest extends TestCase
{
    use DatabaseTransactions,
        PHPUnitAssertions;

    public function test_tokenclean_left_one_token()
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
        app(TokenClean::class)->execute(['dryrun' => false]);

        $this->assertDatabaseHas('synctoken', [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ]);
    }

    public function test_tokenclean_left_all_token()
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
        $s3 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now()->addDays(-15),
        ]);
        $s4 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now()->addDays(-1),
        ]);
        app(TokenClean::class)->execute(['dryrun' => false]);

        $this->assertDatabaseHas('synctoken', [
            'id' => $s1->id,
        ]);
        $this->assertDatabaseMissing('synctoken', [
            'id' => $s2->id,
        ]);
        $this->assertDatabaseMissing('synctoken', [
            'id' => $s3->id,
        ]);
        $this->assertDatabaseHas('synctoken', [
            'id' => $s4->id,
        ]);
    }
}
