<?php

namespace App\Console\Commands\Helpers;

use Illuminate\Console\Command;

interface CommandCallerContract
{
    /**
     * Print a message on the console, then execute a command.
     *
     * @param  Command  $command  Laravel command context
     * @param  string  $message  Message to output
     * @param  string  $commandline  Command to execute
     */
    public function exec(Command $command, string $message, string $commandline): void;

    /**
     * Print a message on the console, then execute an artisan command.
     *
     * @param  Command  $command  Laravel command context
     * @param  string  $message  Message to output
     * @param  string  $commandline  Artisan command name to execute
     * @param  array  $arguments  Optional arguments to pass to the artisan command
     */
    public function artisan(Command $command, string $message, string $commandline, array $arguments = []): void;
}
