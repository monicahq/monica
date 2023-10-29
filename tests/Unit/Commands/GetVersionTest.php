<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;

class GetVersionTest extends TestCase
{
    /** @test */
    public function it_run_setup_command(): void
    {
        config(['monica.app_version' => '1.0.0']);

        $this->artisan('monica:getversion')
            ->expectsOutput('1.0.0')
            ->assertExitCode(0);
    }
}
