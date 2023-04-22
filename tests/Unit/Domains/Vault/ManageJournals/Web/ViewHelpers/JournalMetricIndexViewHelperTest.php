<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalMetricIndexViewHelper;
use App\Models\Journal;
use App\Models\JournalMetric;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalMetricIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'name',
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
            'label' => 'this is a title',
        ]);

        $array = JournalMetricIndexViewHelper::data($journal);

        $this->assertCount(3, $array);
        $this->assertEquals(
            [
                'id' => $journal->id,
                'name' => 'name',
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                ],
            ],
            $array['journal']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $journalMetric->id,
                    'label' => 'this is a title',
                    'url' => [
                        'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/metrics/'.$journalMetric->id,
                    ],
                ],
            ],
            $array['journalMetrics']->toArray()
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'name',
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
            'label' => 'this is a title',
        ]);

        $this->assertEquals(
            [
                'id' => $journalMetric->id,
                'label' => 'this is a title',
                'url' => [
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/metrics/'.$journalMetric->id,
                ],
            ],
            JournalMetricIndexViewHelper::dto($journalMetric)
        );
    }
}
