<?php

namespace Tests\Unit\Domains\Contact\DAV\Jobs;

use App\Domains\Contact\Dav\Jobs\CleanSyncToken;
use App\Models\Account;
use App\Models\SyncToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CleanSyncTokenTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function tokenclean_left_one_token()
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
            'account_id' => $account->id,
        ]);

        SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now(),
        ]);
        (new CleanSyncToken)->execute([]);

        $this->assertDatabaseHas('sync_tokens', [
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
        ]);
    }

    /** @test */
    public function tokenclean_left_all_token()
    {
        $account = Account::factory()->create();
        $user = User::factory()->create([
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
            'timestamp' => now()->addDays(-31),
        ]);
        $s3 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now()->addDays(-45),
        ]);
        $s4 = SyncToken::create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'name' => 'contacts',
            'timestamp' => now()->addDays(-1),
        ]);
        (new CleanSyncToken)->execute([]);

        $this->assertDatabaseHas('sync_tokens', [
            'id' => $s1->id,
        ]);
        $this->assertDatabaseMissing('sync_tokens', [
            'id' => $s2->id,
        ]);
        $this->assertDatabaseMissing('sync_tokens', [
            'id' => $s3->id,
        ]);
        $this->assertDatabaseHas('sync_tokens', [
            'id' => $s4->id,
        ]);
    }
}
