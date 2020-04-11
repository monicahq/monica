<?php

namespace Tests\Commands;

use Tests\TestCase;
use App\Console\Commands\Passport;
use Laravel\Passport\PersonalAccessClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PassportCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function passport_command_create()
    {
        $app = $this->createApplication();
        $app->make('config')->set(['passport.private_key' => '', 'passport.public_key' => '']);
        foreach (PersonalAccessClient::all() as $client) {
            $client->delete();
        }

        $commandExecutor = new CommandExecutorTester();
        $command = new Passport();
        $command->commandExecutor = $commandExecutor;
        $command->setLaravel($app);

        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(1, $commandExecutor->buffer);
        $this->assertCommandContains($commandExecutor->buffer[0], '✓ Creating personal access client', 'php artisan passport:client');
    }

    /** @test */
    public function passport_command_alreday_created()
    {
        $app = $this->createApplication();
        $app->make('config')->set(['passport.private_key' => '', 'passport.public_key' => '']);
        PersonalAccessClient::create();

        $commandExecutor = new CommandExecutorTester();
        $command = new Passport();
        $command->commandExecutor = $commandExecutor;
        $command->setLaravel($app);

        $command->run(new \Symfony\Component\Console\Input\ArrayInput([]), new \Symfony\Component\Console\Output\NullOutput());

        $this->assertCount(0, $commandExecutor->buffer);
    }

    private function assertCommandContains($array, $message, $command)
    {
        $this->assertStringContainsString($message, $array['message']);
        $this->assertStringContainsString($command, $array['command']);
    }
}
