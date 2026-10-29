<?php

declare(strict_types=1);

namespace Tests\Unit\Casts;

use App\Casts\CollationKey;
use App\Models\Contact;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollationKeyTest extends TestCase
{
    #[Test]
    public function it_reads_a_stream_as_the_bytes_it_holds_every_time(): void
    {
        $bytes = "\x29\x5c\xa0\x01";
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $bytes);

        $cast = new CollationKey;
        $contact = new Contact;

        $this->assertSame($bytes, $cast->get($contact, 'sort_key', $stream, []));
        $this->assertSame($bytes, $cast->get($contact, 'sort_key', $stream, []));
    }

    #[Test]
    public function it_reads_a_string_and_null_unchanged(): void
    {
        $cast = new CollationKey;
        $contact = new Contact;

        $this->assertSame("\x29\xa0", $cast->get($contact, 'sort_key', "\x29\xa0", []));
        $this->assertNull($cast->get($contact, 'sort_key', null, []));
    }

    #[Test]
    public function it_writes_a_string_unchanged_on_a_connection_that_binds_text(): void
    {
        $contact = new Contact;
        $contact->setConnection('sqlite');

        $this->assertSame("\x29\xa0", (new CollationKey)->set($contact, 'sort_key', "\x29\xa0", []));
    }

    #[Test]
    public function it_writes_a_stream_holding_the_bytes_on_postgres(): void
    {
        $contact = new Contact;
        $contact->setConnection('pgsql');

        $written = (new CollationKey)->set($contact, 'sort_key', "\x29\xa0", []);

        $this->assertIsResource($written);
        $this->assertSame("\x29\xa0", stream_get_contents($written));
    }
}
