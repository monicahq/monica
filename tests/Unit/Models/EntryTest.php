<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Journal\Entry;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EntryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_info_for_journal_entry()
    {
        $entry = factory(Entry::class)->make([
            'id' => 1,
            'title' => 'This is the title',
            'post' => 'this is a post',
            'created_at' => '2017-01-01 00:00:00',
        ]);

        $data = [
            'type' => 'entry',
            'id' => 1,
            'title' => 'This is the title',
            'post' => '<p>this is a post</p>',
            'day' => 1,
            'day_name' => 'Sun',
            'month' => 1,
            'month_name' => 'JAN',
            'year' => 2017,
            'date' => '2017-01-01 00:00:00',
        ];

        $this->assertEquals(
            $data,
            $entry->getInfoForJournalEntry()
        );
    }
}
