<?php

namespace Tests\Api;

use Carbon\Carbon;
use Tests\ApiTestCase;
use App\Models\Contact\Note;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiNotesTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonNote = [
        'id',
        'object',
        'body',
        'is_favorited',
        'favorited_at',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_all_the_notes()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note1 = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note2 = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/notes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonNote],
        ]);
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note2->id,
        ]);
    }

    /** @test */
    public function it_gets_all_the_notes_of_a_given_contact()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note1 = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note2 = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/notes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonNote],
        ]);
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'note',
            'id' => $note2->id,
        ]);
    }

    /** @test */
    public function it_cant_get_notes_from_a_contact_with_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/notes');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_gets_one_note()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note1 = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $note2 = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/notes/'.$note1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonNote,
        ]);
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'note',
            'id' => $note2->id,
        ]);
    }

    /** @test */
    public function it_gets_a_note_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/notes/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_note()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/notes', [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonNote,
        ]);
        $note_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $this->assertGreaterThan(0, $note_id);
        $this->assertDatabaseHas('notes', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);
    }

    /** @test */
    public function it_creates_a_note_and_marks_as_favorite()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/notes', [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonNote,
        ]);
        $note_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => true,
            'favorited_at' => '2018-01-01T07:00:00Z',
        ]);

        $this->assertGreaterThan(0, $note_id);
        $this->assertDatabaseHas('notes', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => true,
            'favorited_at' => '2018-01-01',
        ]);
    }

    /** @test */
    public function it_cant_create_a_note_with_missing_parameters()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/notes', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The body field is required.',
        ]);
    }

    /** @test */
    public function it_cant_create_a_note_with_an_invalid_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/notes', [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_updates_a_note()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/notes/'.$note->id, [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonNote,
        ]);
        $note_id = $response->json('data.id');
        $this->assertEquals($note->id, $note_id);
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $this->assertGreaterThan(0, $note_id);
        $this->assertDatabaseHas('notes', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);
    }

    /** @test */
    public function it_updates_a_note_and_marks_it_as_favorite()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/notes/'.$note->id, [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonNote,
        ]);
        $note_id = $response->json('data.id');
        $this->assertEquals($note->id, $note_id);
        $response->assertJsonFragment([
            'object' => 'note',
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => true,
            'favorited_at' => '2018-01-01T07:00:00Z',
        ]);

        $this->assertGreaterThan(0, $note_id);
        $this->assertDatabaseHas('notes', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => true,
            'favorited_at' => '2018-01-01',
        ]);
    }

    /** @test */
    public function it_cant_update_a_note_with_missing_parameters()
    {
        $user = $this->signin();
        $note = factory(Note::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/notes/'.$note->id, [
            'contact_id' => $note->contact_id,
        ]);

        $this->expectDataError($response, [
            'The body field is required.',
        ]);
    }

    /** @test */
    public function it_cant_update_a_note_with_an_invalid_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/notes/'.$note->id, [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_deletes_a_note()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('notes', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $note->id,
        ]);

        $response = $this->json('DELETE', '/api/notes/'.$note->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('notes', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $note->id,
        ]);
    }

    /** @test */
    public function it_cant_delete_a_note_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/notes/0');

        $this->expectNotFound($response);
    }
}
