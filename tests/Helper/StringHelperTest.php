<?php

namespace Tests\Helper;

use Tests\TestCase;
use App\Helpers\StringHelper;

class StringHelperTest extends TestCase
{
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
