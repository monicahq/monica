<?php

namespace Tests\Commands;

use Tests\TestCase;
use App\Console\Commands\Update;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function update_command_default()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update();
        $command->commandExecutor = $commandExecutor;
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(9, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($commandExecutor->buffer[2], 'Clear config cache', 'php artisan config:clear');
        $this->assertCommandContains($commandExecutor->buffer[3], 'Clear route cache', 'php artisan route:clear');
        $this->assertCommandContains($commandExecutor->buffer[4], 'Clear view cache', 'php artisan view:clear');
        $this->assertCommandContains($commandExecutor->buffer[5], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[6], 'Check for encryption keys', 'php artisan monica:passport');
        $this->assertCommandContains($commandExecutor->buffer[7], 'Ping for new version', 'php artisan monica:ping');
        $this->assertCommandContains($commandExecutor->buffer[8], 'Maintenance mode: off', 'php artisan up');
    }

    /** @test */
    public function update_command_composer()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update();
        $command->commandExecutor = $commandExecutor;
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput(['--composer-install' => true]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(10, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($commandExecutor->buffer[2], 'Clear config cache', 'php artisan config:clear');
        $this->assertCommandContains($commandExecutor->buffer[3], 'Clear route cache', 'php artisan route:clear');
        $this->assertCommandContains($commandExecutor->buffer[4], 'Clear view cache', 'php artisan view:clear');
        $this->assertCommandContains($commandExecutor->buffer[5], 'Updating composer dependencies', 'composer install');
        $this->assertCommandContains($commandExecutor->buffer[6], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[7], 'Check for encryption keys', 'php artisan monica:passport');
        $this->assertCommandContains($commandExecutor->buffer[8], 'Ping for new version', 'php artisan monica:ping');
        $this->assertCommandContains($commandExecutor->buffer[9], 'Maintenance mode: off', 'php artisan up');
    }

    private function assertCommandContains($array, $message, $command)
    {
        $this->assertStringContainsString($message, $array['message']);
        $this->assertStringContainsString($command, $array['command']);
    }
}
