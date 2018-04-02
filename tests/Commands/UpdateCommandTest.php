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

        $this->assertCount(7, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($commandExecutor->buffer[5], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[6], 'Maintenance mode: off', 'php artisan up');
    }

    public function test_update_command_composer()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update($commandExecutor);
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput(['--composer-install' => true]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(8, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($commandExecutor->buffer[5], 'Updating composer dependencies', 'composer install');
        $this->assertCommandContains($commandExecutor->buffer[6], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[7], 'Maintenance mode: off', 'php artisan up');
    }

    public function assertCommandContains($array, $message, $command)
    {
        $this->assertContains($message, $array['message']);
        $this->assertContains($command, $array['command']);
    }
}
