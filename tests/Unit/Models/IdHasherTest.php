<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Helpers\IdHasher;
use App\Models\Contact\Contact;
use App\Models\Contact\Activity;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IdHasherTest extends TestCase
{
    use DatabaseTransactions;

    public function testPrependH()
    {
        $IdHasher = new IdHasher();

        $test_id = rand();

        $test_hash = $IdHasher->encodeId($test_id);

        $value = substr($test_hash, 0, 1);

        $this->assertEquals('h', $value);
    }

    public function testGetIDback()
    {
        $IdHasher = new IdHasher();

        $test_id = rand();

        $test_hash = $IdHasher->encodeId($test_id);

        $result_id = $IdHasher->decodeId($test_hash);

        $this->assertEquals($test_id, $result_id);
    }

    /**
     * @expectedException App\Exceptions\WrongIdException
     */
    public function test_bad_id_get_exception()
    {
        $IdHasher = new IdHasher();

        $test_id = rand();

        $IdHasher->decodeId($test_id);
    }

    public function testHashIDContact()
    {
        $IdHasher = new IdHasher();

        $contact = factory(Contact::class)->create();

        $value = $IdHasher->decodeId($contact->hashID());

        $this->assertEquals($contact->id, $value);
    }

    public function testHashIDActivity()
    {
        $IdHasher = new IdHasher();

        $activity = factory(Activity::class)->create();

        $value = $IdHasher->decodeId($activity->hashID());

        $this->assertEquals($activity->id, $value);
    }
}
