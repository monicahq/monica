<?php

namespace Tests\Unit;

use App\Debt;
use App\Contact;
use App\Activity;
use App\Reminder;
use Tests\TestCase;
use App\Helpers\IdHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ID_hasherTest extends TestCase
{
    use DatabaseTransactions;

    public function testPrependH()
    {
        $ID_hasher = new IdHasher();

        $test_id = rand();

        $test_hash = $ID_hasher->encodeId($test_id);

        $test_value = ('h' == substr($test_hash, 0, 1));

        $this->assertTrue($test_value);
    }

    public function testGetIDback()
    {
        $ID_hasher = new IdHasher();

        $test_id = rand();

        $test_hash = $ID_hasher->encodeId($test_id);

        $test_value = ($test_id == $ID_hasher->decodeId($test_hash));

        $this->assertTrue($test_value);

        $test_value = ($test_id == $ID_hasher->decodeId($test_id));

        $this->assertTrue($test_value);
    }

    public function testHashIDContact()
    {
        $ID_hasher = new IdHasher();

        $contact = factory(Contact::class)->create();

        $test_hash = $contact->hashID();

        $test_value = ($contact->id == $ID_hasher->decodeId($test_hash));

        $this->assertTrue($test_value);
    }

    public function testHashIDActivity()
    {
        $ID_hasher = new IdHasher();

        $activity = factory(Activity::class)->create();

        $test_hash = $activity->hashID();

        $test_value = ($activity->id == $ID_hasher->decodeId($test_hash));

        $this->assertTrue($test_value);
    }

    public function testHashIDDebt()
    {
        $ID_hasher = new IdHasher();

        $debt = factory(Debt::class)->create();

        $test_hash = $debt->hashID();

        $test_value = ($debt->id == $ID_hasher->decodeId($test_hash));

        $this->assertTrue($test_value);
    }

    public function testHashIDReminder()
    {
        $ID_hasher = new IdHasher();

        $reminder = factory(Reminder::class)->create();

        $test_hash = $reminder->hashID();

        $test_value = ($reminder->id == $ID_hasher->decodeId($test_hash));

        $this->assertTrue($test_value);
    }
}
