<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Journal\Entry;
use App\Models\Journal\JournalEntry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class JournalEntryTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_user_can_add_a_journal_entry()
    {
        $user = $this->signIn();

        $params = [
            'entry' => 'Good day',
            'date' => '2018-01-01',
        ];

        $response = $this->post('/journal/create', $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $user->account_id,
            'date' => '2018-01-01 00:00:00',
            'journalable_type' => 'App\Models\Journal\Entry',
        ]);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account_id,
            'post' => 'Good day',
        ]);
    }

    public function test_user_can_edit_a_journal_entry()
    {
        $user = $this->signIn();

        $entry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
            'title' => 'This is the title',
            'post' => 'this is a post',
        ]);
        $entry->date = '2017-01-01';
        $journalEntry = JournalEntry::add($entry);

        $params = [
            'entry' => 'Good day',
            'date' => '2018-01-01',
        ];

        $response = $this->put('/journal/entries/'.$entry->id, $params);

        $response->assertStatus(302);

        $this->assertDatabaseHas('journal_entries', [
            'account_id' => $user->account_id,
            'date' => '2018-01-01 00:00:00',
            'journalable_id' => $entry->id,
            'journalable_type' => 'App\Models\Journal\Entry',
        ]);
        $this->assertDatabaseHas('entries', [
            'account_id' => $user->account_id,
            'post' => 'Good day',
        ]);
    }

    public function test_user_can_delete_a_journal_entry()
    {
        $user = $this->signIn();

        $entry = factory(Entry::class)->create([
            'account_id' => $user->account_id,
            'title' => 'This is the title',
            'post' => 'this is a post',
        ]);
        $entry->date = '2017-01-01';
        $journalEntry = JournalEntry::add($entry);

        $response = $this->delete('/journal/'.$entry->id);
        $response->assertSuccessful();

        $this->assertDatabaseMissing('entries', [
            'id' => $entry->id,
        ]);
        $this->assertDatabaseMissing('journal_entries', [
            'id' => $journalEntry->id,
        ]);
    }
}
