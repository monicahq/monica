<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use function Safe\json_decode;
use App\Helpers\InstanceHelper;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InstanceHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_latest_changelog_entries()
    {
        $json = public_path('changelog.json');
        $changelogs = json_decode(file_get_contents($json), true)['entries'];
        $count = count($changelogs);

        $this->assertCount(
            $count,
            InstanceHelper::getChangelogEntries()
        );

        $this->assertCount(
            3,
            InstanceHelper::getChangelogEntries(3)
        );
    }

    /** @test */
    public function it_checks_if_the_instance_has_at_least_one_account()
    {
        DB::table('accounts')->delete();

        $this->assertFalse(
            InstanceHelper::hasAtLeastOneAccount()
        );

        factory(Account::class)->create();
        $this->assertTrue(
            InstanceHelper::hasAtLeastOneAccount()
        );
    }
}
