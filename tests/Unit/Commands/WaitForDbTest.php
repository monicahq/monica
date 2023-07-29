<?php

namespace Tests\Unit\Commands;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WaitForDbTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_run_WaitForDb_command(): void
    {
        $this->artisan('monica:waitfordb')
            ->expectsOutput('Database ready.')
            ->assertExitCode(0);
    }
}
