<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Services\Instance\IdHasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IdHasherTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_prepends_the_id_with_the_letter_h()
    {
        $idHasher = new IdHasher();

        $test_id = rand();

        $test_hash = $idHasher->encodeId($test_id);

        $value = substr($test_hash, 0, 1);

        $this->assertEquals('h', $value);
    }

    /** @test */
    public function it_returns_the_id_back()
    {
        $idHasher = new IdHasher();

        $test_id = rand();

        $test_hash = $idHasher->encodeId($test_id);

        $result_id = $idHasher->decodeId($test_hash);

        $this->assertEquals($test_id, $result_id);
    }

    /** @test */
    public function it_gets_an_exception_when_the_id_is_not_valid()
    {
        $idHasher = new IdHasher();

        $test_id = rand();

        $this->expectException(\App\Exceptions\WrongIdException::class);

        $idHasher->decodeId($test_id);
    }

    /** @test */
    public function it_decodes_the_hash_and_returns_the_right_id()
    {
        $idHasher = new IdHasher();

        $contact = factory(Contact::class)->create();

        $value = $idHasher->decodeId($contact->hashID());

        $this->assertEquals($contact->id, $value);
    }
}
