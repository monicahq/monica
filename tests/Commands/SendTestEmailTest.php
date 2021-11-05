<?php

namespace Tests\Commands;

use Mockery as m;
use Tests\TestCase;

class SendTestEmailTest extends TestCase
{
    /** @test */
    public function error_for_bad_email()
    {
        $this->withoutMockingConsoleOutput();

        $exampleEmail = 'no.at.symbol';

        $command = m::mock('\App\Console\Commands\SendTestEmail[error]');

        $command
            ->shouldReceive('error')
            ->once()
            ->with("Invalid email address: \"$exampleEmail\".");

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('monica:test-email', ['--email' => $exampleEmail]);
        $this->assertEquals(-1, $exitCode);
    }

    /** @test */
    public function command_prompts_for_email()
    {
        $this->withoutMockingConsoleOutput();

        $command = m::mock('\App\Console\Commands\SendTestEmail[ask]');

        $command
            ->shouldReceive('ask')
            ->once()
            ->with('What email address should I send the test email to?');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('monica:test-email');
        $this->assertEquals(-1, $exitCode);
    }

    /**
     * test deactivated.
     *
     * Required to prevent alias mock breaking other tests:
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function command_attempts_to_send_email()
    {
        $this->withoutMockingConsoleOutput();

        $exampleEmail = 'test@example.org';

        $externalMock = m::mock('alias:\Illuminate\Support\Facades\Mail');
        $externalMock
            ->shouldReceive('raw')
            ->once()
            ->with("Hi $exampleEmail, you requested a test email from Monica.", m::any());

        $command = m::mock('\App\Console\Commands\SendTestEmail[Mail::raw]');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('monica:test-email', ['--email' => $exampleEmail]);
        $this->assertEquals(0, $exitCode);
    }
}
