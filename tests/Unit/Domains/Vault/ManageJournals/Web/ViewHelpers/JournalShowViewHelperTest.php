<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Vault\ManageJournals\Web\ViewHelpers\JournalShowViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $journal = Journal::factory()->create([
            'name' => 'My Journal',
            'description' => 'My Journal Description',
        ]);

        $array = JournalShowViewHelper::data($journal);
        $this->assertEquals(
            [
                'id' => $journal->id,
                'name' => 'My Journal',
                'description' => 'My Journal Description',
            ],
            $array
        );
    }
}
