<?php

namespace Tests\Unit\Commands;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SetupCommandTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_run_setup_command(): void
    {
        $this->artisan('monica:setup', ['--skip-docs' => true])
            ->expectsOutput('✓ Resetting application cache')
            ->expectsOutput('✓ Clear config cache')
            ->expectsOutput('✓ Clear route cache')
            ->expectsOutput('✓ Clear view cache')
            ->expectsOutput('✓ Performing migrations')
            ->assertExitCode(0);
    }
}
