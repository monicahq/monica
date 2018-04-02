<?php

namespace Tests\Commands;

use Tests\TestCase;
use App\Console\Commands\Update;

class UpdateCommandTest extends TestCase
{
    public function test_update_command_default()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update($commandExecutor);
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(4, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Resetting config cache', 'php artisan config:cache');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[2], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[3], 'Maintenance mode: off', 'php artisan up');
    }

    public function test_update_command_composer()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update($commandExecutor);
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput(['--composer-install' => true]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(5, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Resetting config cache', 'php artisan config:cache');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[2], 'Updating composer dependencies', 'composer install');
        $this->assertCommandContains($commandExecutor->buffer[3], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[4], 'Maintenance mode: off', 'php artisan up');
    }

    public function assertCommandContains($array, $message, $command)
    {
        $this->assertContains($message, $array['message']);
        $this->assertContains($command, $array['command']);
    }
}
