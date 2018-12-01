<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

namespace Tests\Commands;

use Tests\TestCase;
use App\Console\Commands\Update;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_command_default()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update();
        $command->commandExecutor = $commandExecutor;
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(8, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($commandExecutor->buffer[5], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[7], 'Maintenance mode: off', 'php artisan up');
    }

    public function test_update_command_composer()
    {
        $commandExecutor = new CommandExecutorTester();
        $command = new Update();
        $command->commandExecutor = $commandExecutor;
        $command->setLaravel($this->createApplication());

        $command->run(new \Symfony\Component\Console\Input\ArrayInput(['--composer-install' => true]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(9, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], 'Maintenance mode: on', 'php artisan down');
        $this->assertCommandContains($commandExecutor->buffer[1], 'Resetting application cache', 'php artisan cache:clear');
        $this->assertCommandContains($commandExecutor->buffer[5], 'Updating composer dependencies', 'composer install');
        $this->assertCommandContains($commandExecutor->buffer[6], 'Performing migrations', 'php artisan migrate');
        $this->assertCommandContains($commandExecutor->buffer[8], 'Maintenance mode: off', 'php artisan up');
    }

    private function assertCommandContains($array, $message, $command)
    {
        $this->assertContains($message, $array['message']);
        $this->assertContains($command, $array['command']);
    }
}
