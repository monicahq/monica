<?php

namespace Tests\Helper;

use Tests\TestCase;
use App\Helpers\RandomHelper;

class RandomHelperTest extends TestCase
{
    public function testThatGenerateStringReturnsTheRightLength()
    {
        $randomString = RandomHelper::generateString(30);
        $this->assertTrue(30 == strlen($randomString));
    }

    public function testThatGenerateStringIsNotEmpty()
    {
        $randomString = RandomHelper::generateString(30);
        $this->assertNotNull($randomString);
    }
}
