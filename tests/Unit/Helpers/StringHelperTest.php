<?php

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
