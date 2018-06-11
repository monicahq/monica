<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\Changelog;
use App\Jobs\AddChangelogEntry;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddChangelogEntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_adds_a_changelog_entry()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $changelog = factory(Changelog::class)->create([]);

        dispatch(new AddChangelogEntry($account, $changelog->id));

        $this->assertDatabaseHas('changelog_user', [
            'user_id' => $user->id,
            'changelog_id' => $changelog->id,
        ]);
    }
}
