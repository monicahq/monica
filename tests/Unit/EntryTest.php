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
        $entry = new Entry;
        $entry->id = 1;
        $entry->title = 'This is the title';
        $entry->post = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.';
        $entry->created_at = '2017-01-01 00:00:00';
        $entry->save();

        $data = [
            'type' => 'activity',
            'id' => 1,
            'title' => 'This is the title',
            'post' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.',
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
