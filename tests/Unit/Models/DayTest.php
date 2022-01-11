<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Journal\Day;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DayTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function get_info_for_journal_entry_that_doesnt_happen_today()
    {
        $day = factory(Day::class)->make();
        $day->id = 1;
        $day->rate = 1;
        $day->comment = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.';
        $day->date = '2017-01-01 00:00:00';
        $day->created_at = '2017-01-01 00:00:00';
        $day->save();

        $data = [
            'type' => 'day',
            'id' => 1,
            'rate' => 1,
            'comment' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.',
            'day' => 1,
            'day_name' => 'Sun',
            'month' => 1,
            'month_name' => 'JAN',
            'year' => 2017,
            'happens_today' => false,
            'date' => Carbon::parse('2017-01-01 00:00:00'),
        ];

        $this->assertEquals(
            $data,
            $day->getInfoForJournalEntry()
        );
    }

    /** @test */
    public function get_info_for_journal_entry_that_happen_today()
    {
        $date = now();

        $day = factory(Day::class)->make();
        $day->id = 1;
        $day->rate = 1;
        $day->comment = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.';
        $day->date = $date;
        $day->created_at = '2017-01-01 00:00:00';
        $day->save();

        $data = [
            'type' => 'day',
            'id' => 1,
            'rate' => 1,
            'comment' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur. Donec ut libero sed arcu vehicula ultricies a non tortor.',
            'day' => $date->day,
            'day_name' => $date->format('D'),
            'month' => $date->month,
            'month_name' => strtoupper($date->format('M')),
            'year' => $date->year,
            'happens_today' => true,
            'date' => $date->addMicroseconds(-1 * $date->microsecond),
        ];

        $this->assertEquals(
            $data,
            $day->getInfoForJournalEntry()
        );
    }
}
