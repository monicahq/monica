<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\Vault;
use App\Vault\ManageJournals\Web\ViewHelpers\JournalIndexViewHelper;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Journal 1',
            'description' => 'Description 1',
        ]);

        $array = JournalIndexViewHelper::data($vault);

        $this->assertEquals(
            [
                0 => [
                    'id' => $journal->id,
                    'name' => 'Journal 1',
                    'description' => 'Description 1',
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                    ],
                ],
            ],
            $array['journals']->toArray()
        );
        $this->assertEquals(
            [
                'create' => env('APP_URL').'/vaults/'.$vault->id.'/journals/create',
            ],
            $array['url']
        );
    }
}
