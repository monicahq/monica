<?php

namespace App\Console\Commands\Helpers;

use function Safe\exec;
use Illuminate\Console\Command;
use Illuminate\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;

class CommandCaller implements CommandCallerContract
{
    /**
     * Print a message on the console, then execute a command.
     *
     * @param  Command  $command  Laravel command context
     * @param  string  $message  Message to output
     * @param  string  $commandline  Command to execute
     *
     * @codeCoverageIgnore
     */
    public function exec(Command $command, string $message, string $commandline): void
    {
        $command->info($message);
        $command->line($commandline, null, OutputInterface::VERBOSITY_VERBOSE);
        exec($commandline.' 2>&1', $output);
        foreach ($output as $line) {
            $command->line($line, null, OutputInterface::VERBOSITY_VERY_VERBOSE);
        }
        $command->line('', null, OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Print a message on the console, then execute an artisan command.
     *
     * @param  Command  $command  Laravel command context
     * @param  string  $message  Message to output
     * @param  string  $commandline  Artisan command name to execute
     * @param  array  $arguments  Optional arguments to pass to the artisan command
     *
     * @codeCoverageIgnore
     */
    public function artisan(Command $command, string $message, string $commandline, array $arguments = []): void
    {
        $info = '';
        foreach ($arguments as $key => $value) {
            if (is_string($key)) {
                $info .= ' '.$key.'="'.$value.'"';
            } else {
                $info .= ' '.$value;
            }
        }
        $this->exec($command, $message, Application::formatCommandString($commandline.$info));
    }
}
