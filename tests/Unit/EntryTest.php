<?php

namespace Tests\Unit;

use App\Entry;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EntryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_info_for_journal_entry()
    {
        $entry = factory(Entry::class)->make();
        $entry->id = 1;
        $entry->title = 'This is the title';
        $entry->post = 'this is a post';
        $entry->created_at = '2017-01-01 00:00:00';
        $entry->save();

        $data = [
            'type' => 'entry',
            'id' => 1,
            'title' => 'This is the title',
            'post' => '<p>this is a post</p>',
            'day' => 1,
            'day_name' => 'Sun',
            'month' => 1,
            'month_name' => 'Jan',
            'year' => 2017,
        ];

        $this->assertEquals(
            $data,
            $entry->getInfoForJournalEntry()
        );
    }
}
