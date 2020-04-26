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

        $command = m::mock('\App\Console\Commands\SendTestEmail[error]');

        $command
            ->shouldReceive('error')
            ->once()
            ->with('Invalid email address - missing "@" symbol!');

        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $exitCode = $this->artisan('monica:test-email --email no.at.symbol');
        $this->assertEquals(-1, $exitCode);
    }



}
