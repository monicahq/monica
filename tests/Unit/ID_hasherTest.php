<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\ID_hasher;

class ID_hasherTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPrependH()
    {
        $ID_hasher = new ID_hasher();

        $test_id = rand();

        $test_hash = $ID_hasher->encode_id($test_id);

        $test_value = ('h' == substr($test_hash, 0, 1));

        $this->assertTrue($test_value);
    }

    public function testGetIDback()
    {
        $ID_hasher = new ID_hasher();

        $test_id = rand();

        $test_hash = $ID_hasher->encode_id($test_id);

        $test_value = ($test_id == $ID_hasher->decode_id($test_hash));

        $this->assertTrue($test_value);

        $test_value = ($test_id == $ID_hasher->decode_id($test_id));

        $this->assertTrue($test_value);
    }
}
