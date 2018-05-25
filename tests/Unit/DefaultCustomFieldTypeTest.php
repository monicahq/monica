<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Settings\CustomFields\DefaultCustomFieldType;

class DefaultCustomFieldTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_retrieves_the_name_of_the_field()
    {
        $defaultCustomFieldType = factory(DefaultCustomFieldType::class)->make([
            'type' => 'Flirt',
        ]);

        $this->assertEquals(
            'Flirt',
            $defaultCustomFieldType->type
        );
    }
}
