<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\BaseService;

class BaseServiceTest extends TestCase
{
    public function test_it_validates_data_structure()
    {
        $base = new BaseService;

        $data = [
            'item_1' => 'lorem',
            'item_2' => 'lorem',
        ];

        $structure = [
            'item_1',
            'item_2',
        ];

        $this->assertTrue(
            $base->validateDataStructure($data, $structure)
        );
    }

    public function test_it_returns_false_if_wrong_structure()
    {
        $base = new BaseService;

        $data = [
            'item_1' => 'lorem',
            'item_2' => 'lorem',
        ];

        $structure = [
            'item_1',
            'item_3',
        ];

        $this->assertFalse(
            $base->validateDataStructure($data, $structure)
        );
    }
}
