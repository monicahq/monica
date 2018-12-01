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

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\StringHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StringHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_builds_a_sql_query()
    {
        $array = [
            'column1',
            'column2',
            'column3',
        ];

        $searchTerm = 'term';

        $this->assertEquals(
            "column1 LIKE '%term%' or column2 LIKE '%term%' or column3 LIKE '%term%'",
            StringHelper::buildQuery($array, $searchTerm)
        );
    }
}
