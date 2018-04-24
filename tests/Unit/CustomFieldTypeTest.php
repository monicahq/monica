<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomFieldTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_retrieves_the_name_of_the_field()
    {
        $customFieldType = factory('App\CustomFieldType')->make([
            'type' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $customFieldType->type
        );
    }
}
