<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Jobs\AddChangelogEntry;
use App\Models\Account\Account;
use App\Models\Instance\Instance;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_creates_jobs_for_adding_a_unread_changelog_entry()
    {
        Bus::fake();

        $instance = factory(Instance::class)->create([]);
        $account = factory(Account::class, 3)->create([]);

        $instance->addUnreadChangelogEntry(1);

        Bus::assertDispatched(AddChangelogEntry::class);
    }
}
