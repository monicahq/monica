<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeIndexViewHelper;
use App\Models\Journal;
use App\Models\SliceOfLife;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SliceOfLifeIndexViewHelperTest extends TestCase
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
        $slice = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'this is a title',
        ]);

        $array = SliceOfLifeIndexViewHelper::data($journal);

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
                    'id' => $slice->id,
                    'name' => 'this is a title',
                    'date_range' => null,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                    ],
                ],
            ],
            $array['slicesOfLife']->toArray()
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
        $slice = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'this is a title',
        ]);

        $this->assertEquals(
            [
                'id' => $slice->id,
                'name' => 'this is a title',
                'date_range' => null,
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                ],
            ],
            SliceOfLifeIndexViewHelper::dtoSlice($slice)
        );
    }
}
