<?php

namespace Tests\Unit\Commands;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WaitForDbTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_run_wait_for_db_command(): void
    {
        $this->artisan('waitfordb')
            ->expectsOutput('Database ready.')
            ->assertExitCode(0);
    }
}
