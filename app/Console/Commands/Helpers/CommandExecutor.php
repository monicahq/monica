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

namespace App\Console\Commands\Helpers;

use Illuminate\Console\Command;
use Illuminate\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

class CommandExecutor implements CommandExecutorInterface
{
    /**
     * @var Command
     */
    protected $command;

    /**
     * Create a new CommandExecutor.
     *
     * @param Command
     */
    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    public function exec($message, $commandline)
    {
        $this->command->info($message);
        $this->command->line($commandline, null, OutputInterface::VERBOSITY_VERBOSE);
        exec($commandline.' 2>&1', $output);
        foreach ($output as $line) {
            $this->command->line($line, null, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        $this->command->line('', null, OutputInterface::VERBOSITY_VERBOSE);
    }

    public function artisan($message, $commandline, array $arguments = [])
    {
        $info = '';
        foreach ($arguments as $key => $value) {
            if (is_string($key)) {
                $info .= ' '.$key.'="'.$value.'"';
            } else {
                $info .= ' '.$value;
            }
        }
        $this->exec($message, Application::formatCommandString($commandline.$info));
    }
}
