<?php

namespace Tests\Commands\Other;

use Tests\TestCase;
use App\Console\Commands\Helpers\Command;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function update_command_default()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        $this->artisan('monica:update')->run();

        $this->assertCount(9, $fake->buffer);
        $this->assertCommandContains($fake->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($fake->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($fake->buffer[2], 'Clear config cache', 'php artisan config:clear');
        $this->assertCommandContains($fake->buffer[3], 'Clear route cache', 'php artisan route:clear');
        $this->assertCommandContains($fake->buffer[4], 'Clear view cache', 'php artisan view:clear');
        $this->assertCommandContains($fake->buffer[5], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($fake->buffer[6], 'Check for encryption keys', 'php artisan monica:passport');
        $this->assertCommandContains($fake->buffer[7], 'Ping for new version', 'php artisan monica:ping');
        $this->assertCommandContains($fake->buffer[8], 'Maintenance mode: off', 'php artisan up');
    }

    /** @test */
    public function update_command_composer()
    {
        /** @var \Tests\Helpers\CommandCallerFake */
        $fake = Command::fake();

        $this->artisan('monica:update', ['--composer-install' => true])->run();

        $this->assertCount(10, $fake->buffer);
        $this->assertCommandContains($fake->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($fake->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($fake->buffer[2], 'Clear config cache', 'php artisan config:clear');
        $this->assertCommandContains($fake->buffer[3], 'Clear route cache', 'php artisan route:clear');
        $this->assertCommandContains($fake->buffer[4], 'Clear view cache', 'php artisan view:clear');
        $this->assertCommandContains($fake->buffer[5], 'Updating composer dependencies', 'composer install');
        $this->assertCommandContains($fake->buffer[6], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($fake->buffer[7], 'Check for encryption keys', 'php artisan monica:passport');
        $this->assertCommandContains($fake->buffer[8], 'Ping for new version', 'php artisan monica:ping');
        $this->assertCommandContains($fake->buffer[9], 'Maintenance mode: off', 'php artisan up');
    }

    private function assertCommandContains($array, $message, $command)
    {
        $this->assertStringContainsString($message, $array['message']);
        $this->assertStringContainsString($command, $array['command']);
    }
}
