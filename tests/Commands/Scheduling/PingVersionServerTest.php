<?php

namespace Tests\Commands\Scheduling;

use Tests\TestCase;
use App\Models\Instance\Instance;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PingVersionServerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_send_ping()
    {
        config(['monica.weekly_ping_server_url' => 'https://version.test/ping']);
        config(['monica.app_version' => '2.9.0']);
        config(['monica.check_version' => true]);

        Instance::all()->each(function ($instance) {
            $instance->delete();
        });
        $instance = factory(Instance::class)->create();

        $ret = [
            'new_version' => true,
            'latest_version' => '3.1.0',
            'number_of_versions_since_user_version' => 2,
            'notes' => 'notes',
        ];

        Http::fake([
            'https://version.test/*' => Http::response($ret, 200),
        ]);

        $this->artisan('monica:ping')->run();

        $instance->refresh();

        $this->assertEquals('3.1.0', $instance->latest_version);
        $this->assertEquals('notes', $instance->latest_release_notes);
        $this->assertEquals(2, $instance->number_of_versions_since_current_version);
    }

    /** @test */
    public function it_clear_instance()
    {
        config(['monica.weekly_ping_server_url' => 'https://version.test/ping']);
        config(['monica.app_version' => '3.1.0']);

        Instance::all()->each(function ($instance) {
            $instance->delete();
        });
        $instance = factory(Instance::class)->create([
            'latest_version' => '3.1.0',
        ]);

        $ret = [
            'new_version' => false,
            'latest_version' => '2.9.0',
            'number_of_versions_since_user_version' => 0,
            'notes' => '',
        ];

        Http::fake([
            'https://version.test/*' => Http::response($ret, 200),
        ]);

        $this->artisan('monica:ping')->run();

        $instance->refresh();

        $this->assertEquals('3.1.0', $instance->latest_version);
        $this->assertNull($instance->latest_release_notes);
        $this->assertNull($instance->number_of_versions_since_current_version);
    }

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
