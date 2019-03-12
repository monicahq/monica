<?php

namespace Tests\Commands;

use App\Console\Commands\Helpers\CommandExecutorInterface;

class CommandExecutorTester implements CommandExecutorInterface
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $buffer;

    /**
     * Create a new CommandExecutorTester.
     */
    public function __construct()
    {
        $this->buffer = collect([]);
    }

    public function exec($message, $commandline)
    {
        $this->buffer->push(['message' => $message, 'command' => $commandline]);
    }

    public function artisan($message, $commandline, array $arguments = [])
    {
        $info = '';
        foreach ($arguments as $key => $value) {
            $info = $info.' '.$key.'='.$value;
        }
        $this->buffer->push(['message' =>$message, 'command' => 'php artisan '.$commandline.$info]);
    }
}
