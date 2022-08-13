<?php

namespace Tests\Commands\Tests;

use Tests\TestCase;
use App\Console\Commands\Helpers\Command;
use Laravel\Passport\PersonalAccessClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PassportCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        if (! file_exists(base_path('storage/oauth-private.key')) || ! file_exists(base_path('storage/oauth-public.key'))) {
            $this->markTestSkipped('Run "php artisan key:generate" before executing these tests.');
        }

        foreach (PersonalAccessClient::all() as $client) {
            $client->delete();
        }
    }

    /** @test */
    public function passport_command_create()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        $this->artisan('monica:passport')->run();

        $this->assertCount(1, $fake->buffer, $fake->buffer->implode(','));
        $this->assertCommandContains($fake->buffer[0], '✓ Creating personal access client', 'php artisan passport:client');
    }

    /** @test */
    public function passport_command_already_created()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        PersonalAccessClient::create();

        $this->artisan('monica:passport')->run();

        $this->assertCount(0, $fake->buffer, $fake->buffer->implode(','));
    }

    /** @test */
    public function passport_command_env_config()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        config(['passport.private_key' => '-', 'passport.public_key' => '-']);

        $this->artisan('monica:passport')->run();

        $this->assertCount(1, $fake->buffer, $fake->buffer->implode(','));
        $this->assertCommandContains($fake->buffer[0], '✓ Creating personal access client', 'php artisan passport:client');
    }

    private function assertCommandContains($array, $message, $command)
    {
        $this->assertStringContainsString($message, $array['message']);
        $this->assertStringContainsString($command, $array['command']);
    }
}
