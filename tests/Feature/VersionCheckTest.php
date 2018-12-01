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

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class VersionCheckTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * If an instance sets `version_check` env variable to false, the command
     * should exit with 0.
     *
     * @return void
     */
    public function test_check_version_set_to_false_disables_the_check()
    {
        config(['monica.check_version' => false]);
        $this->withoutMockingConsoleOutput();

        $resultCommand = $this->artisan('monica:ping');
        $this->assertEquals(0, $resultCommand);
    }
}
