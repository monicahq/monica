<?php

namespace App\Console\Commands\Helpers;

use Illuminate\Console\Command;
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
        $this->command->line($commandline);
        exec($commandline.' 2>&1', $output);
        if ($this->command->getOutput()->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            foreach ($output as $line) {
                $this->command->line($line);
            }
        }
        $this->command->line('');
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
        $this->exec($message, 'php artisan '.$commandline.$info);
    }
}
