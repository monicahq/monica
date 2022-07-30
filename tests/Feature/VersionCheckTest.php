<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VersionCheckTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * If an instance sets `version_check` env variable to false, the command
     * should exit with 0.
     *
     * @return void
     */
    public function test_check_version_set_to_false_disables_the_check()
    {
        config(['monica.weekly_ping_server_url' => 'https://version.test/ping']);
        config(['monica.app_version' => '2.9.0']);
        config(['monica.check_version' => false]);

        $fake = Http::fake([
            'https://version.test/*' => Http::response([], 500),
        ]);

        $this->artisan('monica:ping')
            ->assertSuccessful()
            ->run();

        $fake->assertNothingSent();
    }
}
