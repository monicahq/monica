<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\User\Changelog;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChangelogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_many_users()
    {
        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create(['account_id' => $account->id]);
        $changelog = factory(Changelog::class)->create([]);
        $changelog->users()->sync($user->id);

        $user = factory(User::class)->create(['account_id' => $account->id]);
        $changelog = factory(Changelog::class)->create([]);
        $changelog->users()->sync($user->id);

        $this->assertTrue($changelog->users()->exists());
    }

    public function test_it_gets_the_description_in_markdown()
    {
        $changelog = factory(Changelog::class)->make([]);
        $changelog->description = '# Test';
        $changelog->save();

        $this->assertEquals(
            '<h1>Test</h1>',
            $changelog->description
        );
    }

    public function test_it_gets_the_created_at_date_in_friendly_format()
    {
        $changelog = factory(Changelog::class)->make([]);
        $changelog->created_at = '1998-02-02 10:10:10';
        $changelog->save();

        $this->assertEquals(
            'Feb 02, 1998',
            $changelog->created_at
        );
    }
}
