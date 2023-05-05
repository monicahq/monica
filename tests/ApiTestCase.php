<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Traits\Asserts;

class ApiTestCase extends TestCase
{
    use Asserts,
        DatabaseTransactions;

    protected function links(string $path): array
    {
        return [
            'first' => env('APP_URL').$path.'?page=1',
            'last' => env('APP_URL').$path.'?page=1',
            'prev' => null,
            'next' => null,
        ];
    }

    protected function meta(string $path): array
    {
        return [
            'current_page' => 1,
            'from' => 1,
            'last_page' => 1,
            'links' => [
                0 => [
                    'url' => null,
                    'label' => '❮ Previous',
                    'active' => false,
                ],
                1 => [
                    'url' => env('APP_URL').$path.'?page=1',
                    'label' => '1',
                    'active' => true,
                ],
                2 => [
                    'url' => null,
                    'label' => 'Next ❯',
                    'active' => false,
                ],
            ],
            'path' => env('APP_URL').$path,
            'per_page' => 10,
            'to' => 1,
            'total' => 1,
        ];
    }
}
