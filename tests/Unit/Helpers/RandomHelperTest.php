<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use App\Helpers\RandomHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RandomHelperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_returns_a_unique_uuid()
    {
        $this->assertEquals(
            36,
            strlen(RandomHelper::uuid())
        );
        $this->assertIsString(RandomHelper::uuid());
    }
}
