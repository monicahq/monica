<?php

namespace Tests\Helpers;

use PHPUnit\Framework\Assert;
use App\Console\Commands\Helpers\CommandCallerContract;

class CommandCallerFake implements CommandCallerContract
{
    /**
     * @var \Illuminate\Support\Collection
     */
    public $buffer;

    /**
     * Create a new CommandCallerTester.
     */
    public function __construct()
    {
        $this->buffer = collect([]);
    }

    public function exec($command, $message, $commandline): void
    {
        $this->buffer->push(['message' => $message, 'command' => $commandline]);
    }

    public function artisan($command, $message, $commandline, array $arguments = []): void
    {
        $info = '';
        foreach ($arguments as $key => $value) {
            $info = $info.' '.$key.'='.$value;
        }
        $this->buffer->push(['message' =>$message, 'command' => 'php artisan '.$commandline.$info]);
    }

    /**
     * Assert the command identified by a message has been launched.
     */
    public function assertContainsMessage(string $message): void
    {
        $messages = $this->buffer->map(function ($line) {
            return $line['message'];
        });
        Assert::assertContains($message, $messages);
    }
}
