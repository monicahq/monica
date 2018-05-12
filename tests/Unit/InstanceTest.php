<?php

namespace Tests\Unit;

use App\Jobs\AddChangelogEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

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
