<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalIndexViewHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\User;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class JournalIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Journal 1',
            'description' => 'Description 1',
        ]);

        Post::factory()->create([
            'journal_id' => $journal->id,
            'written_at' => Carbon::createFromFormat('Y-m-d', '2020-01-01'),
        ]);
        Post::factory()->create([
            'journal_id' => $journal->id,
            'written_at' => Carbon::createFromFormat('Y-m-d', '2019-01-01'),
        ]);

        $array = JournalIndexViewHelper::data($vault, $user);

        $this->assertEquals(
            [
                0 => [
                    'id' => $journal->id,
                    'name' => 'Journal 1',
                    'description' => 'Description 1',
                    'last_updated' => 'Jan 01, 2020',
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
