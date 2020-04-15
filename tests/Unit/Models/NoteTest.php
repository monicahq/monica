<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Note;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NoteTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory(Contact::class)->create(['account_id' => $account->id]);
        $note = factory(Note::class)->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($note->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $note = factory(Note::class)->create([
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($note->contact()->exists());
    }

    /** @test */
    public function it_filters_by_favorited_notes()
    {
        $note = factory(Note::class)->create(['is_favorited' => true]);
        $note = factory(Note::class)->create(['is_favorited' => true]);
        $note = factory(Note::class)->create(['is_favorited' => false]);
        $note = factory(Note::class)->create(['is_favorited' => true]);

        $this->assertEquals(
            3,
            Note::favorited()->count()
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

        $this->assertIsString($note->getCreatedAt());
    }

    public function testGetContentReturnsAString()
    {
        $note = factory(Note::class)->make();

        $this->assertIsString($note->getContent());
    }
}
