<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Journal\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiJournalTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonJournal = [
        'id',
        'object',
        'title',
        'post',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_journal_get_all_journal()
    {
        $user = $this->signin();
        $journal_entry1 = factory(JournalEntry::class)->create([
            'account_id' => $user->account->id,
        ]);
        $journal_entry2 = factory(JournalEntry::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/journal');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonJournal],
        ]);
        $response->assertJsonFragment([
            'object' => 'journal',
            'id' => $journal_entry1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'journal',
            'id' => $journal_entry2->id,
        ]);
    }

    public function test_journal_get_one_journal()
    {
        $user = $this->signin();
        $journal_entry1 = factory(JournalEntry::class)->create([
            'account_id' => $user->account->id,
        ]);
        $journal_entry2 = factory(JournalEntry::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/journal/'.$journal_entry1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $response->assertJsonFragment([
            'object' => 'journal',
            'id' => $journal_entry1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'journal',
            'id' => $journal_entry2->id,
        ]);
    }

    public function test_journal_get_one_journal_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/journal/0');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'error_code' => 31,
            ],
        ]);
    }

    public function test_journal_create_journal()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/journal', [
            'title' => 'my title',
            'post' => 'content post'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $journal_entry_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'journal',
            'id' => $journal_entry_id,
            'title' => 'my title',
            'post' => 'content post'
        ]);

        $this->assertGreaterThan(0, $journal_entry_id);
        $this->assertDatabaseHas('journal', [
            'account_id' => $user->account->id,
            'id' => $journal_entry_id,
            'title' => 'my title',
            'post' => 'content post'
        ]);
    }

    public function test_journal_create_journal_error()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/journal', []);

        $response->assertStatus(200);
        $response->assertJson([
            'error' => [
                'error_code' => 32,
            ],
        ]);
    }

    public function test_journal_update_journal()
    {
        $user = $this->signin();
        $journal_entry = factory(JournalEntry::class)->create([
            'account_id' => $user->account->id,
            'title' => 'xxx',
        ]);

        $response = $this->json('PUT', '/api/journal/'.$journal_entry->id, [
            'title' => 'my title',
            'post' => 'content post'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $journal_entry_id = $response->json('data.id');
        $this->assertEquals($journal_entry->id, $journal_entry_id);
        $response->assertJsonFragment([
            'object' => 'journal',
            'id' => $journal_entry_id,
            'title' => 'my title',
            'post' => 'content post'
        ]);

        $this->assertGreaterThan(0, $journal_entry_id);
        $this->assertDatabaseHas('journal_entry', [
            'account_id' => $user->account->id,
            'id' => $journal_entry_id,
            'title' => 'my title',
            'post' => 'content post'
        ]);
    }

    public function test_journal_delete_journal()
    {
        $user = $this->signin();
        $journal_entry = factory(JournalEntry::class)->create([
            'account_id' => $user->account->id,
        ]);
        $this->assertDatabaseHas('journal', [
            'account_id' => $user->account->id,
            'id' => $journal_entry->id,
        ]);

        $response = $this->json('DELETE', '/api/journal/'.$journal_entry->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('journal', [
            'account_id' => $user->account->id,
            'id' => $journal_entry->id,
        ]);
    }
}
