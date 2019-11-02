<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Account\Activity;
use App\Services\Instance\IdHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IdHasherTest extends TestCase
{
    use DatabaseTransactions;

    public function testPrependH()
    {
        $idHasher = new IdHasher();

        $test_id = rand();

        $test_hash = $idHasher->encodeId($test_id);

        $value = substr($test_hash, 0, 1);

        $this->assertEquals('h', $value);
    }

    public function testGetIDback()
    {
        $idHasher = new IdHasher();

        $test_id = rand();

        $test_hash = $idHasher->encodeId($test_id);

        $result_id = $idHasher->decodeId($test_hash);

        $this->assertEquals($test_id, $result_id);
    }

    public function test_bad_id_get_exception()
    {
        $idHasher = new IdHasher();

        $test_id = rand();

        $this->expectException(\App\Exceptions\WrongIdException::class);

        $idHasher->decodeId($test_id);
    }

    public function testHashIDContact()
    {
        $idHasher = new IdHasher();

        $contact = factory(Contact::class)->create();

        $value = $idHasher->decodeId($contact->hashID());

        $this->assertEquals($contact->id, $value);
    }

    public function testHashIDActivity()
    {
        $idHasher = new IdHasher();

        $activity = factory(Activity::class)->create();

        $value = $idHasher->decodeId($activity->hashID());

        $this->assertEquals($activity->id, $value);
    }
}
