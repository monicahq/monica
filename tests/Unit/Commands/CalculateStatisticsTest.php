<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace Tests\Unit\Commands;

use Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CalculateStatisticsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_command_runs_well()
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
