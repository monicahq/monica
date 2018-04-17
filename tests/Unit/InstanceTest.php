<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\AddChangelogEntry;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_jobs_for_adding_a_unread_changelog_entry()
    {
        Bus::fake();

        $instance = factory('App\Instance')->create([]);
        $account = factory('App\Account', 3)->create([]);

        $instance->addUnreadChangelogEntry(1);

        Bus::assertDispatched(AddChangelogEntry::class);
    }
}
