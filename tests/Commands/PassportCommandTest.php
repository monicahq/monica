<?php

namespace Tests\Commands;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\Helpers\Command;
use Laravel\Passport\PersonalAccessClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PassportCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function passport_command_create()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        $app = $this->createApplication();
        $app->make('config')->set(['passport.private_key' => '', 'passport.public_key' => '']);
        foreach (PersonalAccessClient::all() as $client) {
            $client->delete();
        }

        Artisan::call('monica:passport');

        $this->assertCount(1, $fake->buffer);
        $this->assertCommandContains($fake->buffer[0], '✓ Creating personal access client', 'php artisan passport:client');
    }

    /** @test */
    public function passport_command_already_created()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        $app = $this->createApplication();
        $app->make('config')->set(['passport.private_key' => '', 'passport.public_key' => '']);
        PersonalAccessClient::create();

        Artisan::call('monica:passport');

        $this->assertCount(0, $fake->buffer);
    }

    /** @test */
    public function passport_command_env_config()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        $app = $this->createApplication();
        $app->make('config')->set(['passport.private_key' => '-', 'passport.public_key' => '-']);

        Artisan::call('monica:passport');

        $this->assertCount(0, $fake->buffer);
    }

    private function assertCommandContains($array, $message, $command)
    {
        $this->assertStringContainsString($message, $array['message']);
        $this->assertStringContainsString($command, $array['command']);
    }
}
