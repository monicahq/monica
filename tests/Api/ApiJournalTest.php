<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Journal\Entry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiJournalTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonJournal = [
        'id',
        'object',
        'title',
        'post',
        'date',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_all_the_journal_entries()
    {
        $user = $this->signin();
        $firstEntry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
        ]);
        $secondEntry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/journal');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonJournal],
        ]);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $firstEntry->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $secondEntry->id,
        ]);
    }

    /** @test */
    public function it_gets_one_journal_entry()
    {
        $user = $this->signin();
        $firstEntry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
        ]);
        $secondEntry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/journal/'.$firstEntry->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $firstEntry->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'entry',
            'id' => $secondEntry->id,
        ]);
    }

    /** @test */
    public function it_cant_get_a_journal_entry_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/journal/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_journal_entry()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/journal', [
            'title' => 'my title',
            'post' => 'content post',
            'date' => '2024-01-01',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $entryId = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entryId,
            'title' => 'my title',
            'post' => 'content post',
            'date' => '2024-01-01T00:00:00.000000Z',
        ]);

        $this->assertGreaterThan(0, $entryId);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account_id,
            'id' => $entryId,
            'title' => 'my title',
            'post' => 'content post',
            'date' => '2024-01-01',
        ]);
    }

    /** @test */
    public function it_cant_create_a_journal_entry_with_missing_parameters()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/journal', []);

        $this->expectDataError($response, [
            'The post field is required.',
            'The date field is required.',
        ]);
    }

    /** @test */
    public function it_updates_a_journal_entry()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
            'post' => 'xxx',
            'date' => '2024-01-01',
        ]);

        $response = $this->json('PUT', '/api/journal/'.$entry->id, [
            'title' => 'my title',
            'post' => 'content post',
            'date' => '2024-01-02',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $entryId = $response->json('data.id');
        $this->assertEquals($entry->id, $entryId);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entryId,
            'title' => 'my title',
            'post' => 'content post',
            'date' => '2024-01-02T00:00:00.000000Z',
        ]);

        $this->assertGreaterThan(0, $entryId);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account_id,
            'id' => $entryId,
            'title' => 'my title',
            'post' => 'content post',
            'date' => '2024-01-02',
        ]);
    }

    /** @test */
    public function it_cant_update_a_journal_entry_with_missing_parameters()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/journal/'.$entry->id, []);

        $this->expectDataError($response, [
            'The post field is required.',
            'The date field is required.',
        ]);
    }

    /** @test */
    public function it_deletes_a_journal_entry()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
        ]);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account_id,
            'id' => $entry->id,
        ]);

        $response = $this->json('DELETE', '/api/journal/'.$entry->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('entries', [
            'account_id' => $user->account_id,
            'id' => $entry->id,
        ]);
    }

    /** @test */
    public function it_cant_delete_a_journal_entry_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/journal/0');

        $this->expectDataError($response, [
            'The selected id is invalid.',
        ]);
    }
}
