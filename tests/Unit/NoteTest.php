<?php

namespace Tests\Unit;

use App\Note;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NoteTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetBodyReturnsNullIfUndefined()
    {
        $note = new Note;

        $this->assertNull($note->getBody());
    }

    public function testGetBodyReturnsTextIfDefined()
    {
        $note = new Note;
        $note->body = 'This is a text';

        $this->assertEquals(
            'This is a text',
            $note->getBody()
        );
    }

    public function testGetCreatedAtReturnsAFormattedDate()
    {
        $note = new Note;
        $note->created_at = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertEquals(
            'Jan 22, 2017',
            $note->getCreatedAt($locale)
        );
    }

    public function testGetCreatedAtReturnsAString()
    {
        $note = new Note;
        $note->created_at = '2017-01-22 17:56:03';
        $locale = 'en';

        $this->assertInternalType('string', $note->getCreatedAt($locale));
    }

    public function testGetContentReturnsAString()
    {
        $note = factory(\App\Note::class)->make();

        $this->assertInternalType('string', $note->getContent());
    }
}
