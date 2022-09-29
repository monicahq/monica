<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\Vault;
use App\Vault\ManageJournals\Web\ViewHelpers\PostCreateViewHelper;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostCreateViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $array = PostCreateViewHelper::data($journal);
        $this->assertEquals(
            [
                'url' => [
                    'store' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts',
                    'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts',
                ],
            ],
            $array
        );
    }
}
