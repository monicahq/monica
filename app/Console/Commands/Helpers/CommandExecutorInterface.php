<?php

namespace App\Console\Commands\Helpers;

interface CommandExecutorInterface
{
    public function exec($message, $command);

    public function artisan($message, $command, array $arguments = []);
}
