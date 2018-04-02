<?php

namespace Tests\Unit;

use App\Note;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NoteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_an_account()
    {
        $account = factory('App\Account')->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $note = factory('App\Note')->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($note->account()->exists());
    }

    public function test_it_belongs_to_a_contact()
    {
        $contact = factory('App\Contact')->create([]);
        $note = factory('App\Note')->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($note->contact()->exists());
    }

    public function test_it_filters_by_favorited_notes()
    {
        $note = factory('App\Note')->create(['is_favorited' => true]);
        $note = factory('App\Note')->create(['is_favorited' => true]);
        $note = factory('App\Note')->create(['is_favorited' => false]);
        $note = factory('App\Note')->create(['is_favorited' => true]);

        $this->assertEquals(
            3,
            Note::favorited()->count()
        );
    }

    public function test_it_returns_body_in_markdown()
    {
        $note = new Note;
        $note->body = '# Test';

        $this->assertEquals(
            '<h1>Test</h1>',
            $note->getParsedBodyAttribute()
        );
    }

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

        $this->assertEquals(
            'Jan 22, 2017',
            $note->getCreatedAt()
        );
    }

    public function testGetCreatedAtReturnsAString()
    {
        $note = new Note;
        $note->created_at = '2017-01-22 17:56:03';

        $this->assertInternalType('string', $note->getCreatedAt());
    }

    public function testGetContentReturnsAString()
    {
        $note = factory(\App\Note::class)->make();

        $this->assertInternalType('string', $note->getContent());
    }
}
