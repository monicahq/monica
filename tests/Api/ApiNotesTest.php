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

    public function test_notes_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note1 = factory(Note::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note2 = factory(Note::class)->create([
            'account_id' => $user->account->id,
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

    public function test_notes_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note1 = factory(Note::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note2 = factory(Note::class)->create([
            'account_id' => $user->account->id,
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

    public function test_notes_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/notes');

        $this->expectNotFound($response);
    }

    public function test_notes_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note1 = factory(Note::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $note2 = factory(Note::class)->create([
            'account_id' => $user->account->id,
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

    public function test_notes_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/notes/0');

        $this->expectNotFound($response);
    }

    public function test_notes_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
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
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);
    }

    public function test_notes_create_favorite()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
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
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => true,
            'favorited_at' => '2018-01-01',
        ]);
    }

    public function test_notes_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('POST', '/api/notes', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The body field is required.',
        ]);
    }

    public function test_notes_create_error_bad_account()
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

    public function test_notes_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account->id,
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
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);
    }

    public function test_notes_update_favorite()
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1, 7, 0, 0));

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account->id,
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
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $note_id,
            'body' => 'the body of the note',
            'is_favorited' => true,
            'favorited_at' => '2018-01-01',
        ]);
    }

    public function test_notes_update_error()
    {
        $user = $this->signin();
        $note = factory(Note::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/notes/'.$note->id, [
            'contact_id' => $note->contact_id,
        ]);

        $this->expectDataError($response, [
            'The body field is required.',
        ]);
    }

    public function test_notes_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/notes/'.$note->id, [
            'contact_id' => $contact->id,
            'body' => 'the body of the note',
            'is_favorited' => false,
        ]);

        $this->expectNotFound($response);
    }

    public function test_notes_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $note = factory(Note::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('notes', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $note->id,
        ]);

        $response = $this->json('DELETE', '/api/notes/'.$note->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('notes', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $note->id,
        ]);
    }

    public function test_notes_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/notes/0');

        $this->expectNotFound($response);
    }
}
