<?php

namespace Tests\Commands;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CalculateStatisticsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function the_command_runs_well()
    {
        $runsWell = true;

        try {
            $this->artisan('monica:calculatestatistics');
        } catch (QueryException $e) {
            $runsWell = false;
        }

        $this->assertTrue($runsWell);
    }
}
