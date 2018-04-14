<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChangelogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_many_changelogs()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create([]);
        $changelog->users()->sync($user->id);

        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create([]);
        $changelog->users()->sync($user->id);

        $this->assertTrue($changelog->users()->exists());
    }
}
