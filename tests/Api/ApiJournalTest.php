<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Journal\Entry;
use App\Models\Account\Account;
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
        $entry1 = factory(Entry::class)->create([
            'account_id' => $user->account->id,
        ]);
        $entry2 = factory(Entry::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/journal');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonJournal],
        ]);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entry1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entry2->id,
        ]);
    }

    public function test_journal_get_one_journal()
    {
        $user = $this->signin();
        $entry1 = factory(Entry::class)->create([
            'account_id' => $user->account->id,
        ]);
        $entry2 = factory(Entry::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/journal/'.$entry1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entry1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'entry',
            'id' => $entry2->id,
        ]);
    }

    public function test_journal_get_one_journal_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/journal/0');

        $this->expectNotFound($response);
    }

    public function test_journal_create_journal()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/journal', [
            'title' => 'my title',
            'post' => 'content post',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $entry_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entry_id,
            'title' => 'my title',
            'post' => '<p>content post</p>',
        ]);

        $this->assertGreaterThan(0, $entry_id);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account->id,
            'id' => $entry_id,
            'title' => 'my title',
            'post' => 'content post',
        ]);
    }

    public function test_journal_create_journal_error()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/journal', []);

        $this->expectDataError($response, [
            'The title field is required.',
            'The post field is required.',
        ]);
    }

    public function test_journal_update_journal()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account->id,
            'title' => 'xxx',
        ]);

        $response = $this->json('PUT', '/api/journal/'.$entry->id, [
            'title' => 'my title',
            'post' => 'content post',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonJournal,
        ]);
        $entry_id = $response->json('data.id');
        $this->assertEquals($entry->id, $entry_id);
        $response->assertJsonFragment([
            'object' => 'entry',
            'id' => $entry_id,
            'title' => 'my title',
            'post' => '<p>content post</p>',
        ]);

        $this->assertGreaterThan(0, $entry_id);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account->id,
            'id' => $entry_id,
            'title' => 'my title',
            'post' => 'content post',
        ]);
    }

    public function test_journal_update_error()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('PUT', '/api/journal/'.$entry->id, []);

        $this->expectDataError($response, [
            'The title field is required.',
            'The post field is required.',
        ]);
    }

    public function test_journal_update_error2()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account->id,
            'title' => 'xxx',
        ]);

        $response = $this->json('PUT', '/api/journal/'.$entry->id, []);

        $this->expectDataError($response, [
            'The title field is required.',
            'The post field is required.',
        ]);
    }

    public function test_journal_delete_journal()
    {
        $user = $this->signin();
        $entry = factory(Entry::class)->create([
            'account_id' => $user->account->id,
        ]);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account->id,
            'id' => $entry->id,
        ]);

        $response = $this->json('DELETE', '/api/journal/'.$entry->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('entries', [
            'account_id' => $user->account->id,
            'id' => $entry->id,
        ]);
    }

    public function test_journal_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/journal/0');

        $this->expectNotFound($response);
    }
}
