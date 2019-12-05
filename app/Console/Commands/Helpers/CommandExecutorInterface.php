<?php

namespace App\Console\Commands\Helpers;

interface CommandExecutorInterface
{
    public function exec($message, string $command);

    public function artisan($message, string $command, array $arguments = []);
}
