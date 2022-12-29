<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeEditViewHelper;
use App\Models\Journal;
use App\Models\SliceOfLife;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SliceOfLifeEditViewHelperTest extends TestCase
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

        $array = SliceOfLifeEditViewHelper::data($slice);

        $this->assertEquals(
            [
                'slice' => [
                    'id' => $slice->id,
                    'name' => 'this is a title',
                    'description' => null,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                    ],
                ],
                'journal' => [
                    'id' => $slice->journal->id,
                    'name' => 'name',
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                    ],
                ],
                'url' => [
                    'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                    'slices_index' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices',
                    'update' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                ],
            ],
            $array
        );
    }
}
