<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\AddChangelogEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddChangelogEntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_adds_a_changelog_entry()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create([]);

        dispatch(new AddChangelogEntry($account, $changelog->id));

        $this->assertDatabaseHas('changelog_user', [
            'user_id' => $user->id,
            'changelog_id' => $changelog->id,
        ]);
    }
}
