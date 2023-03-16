<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalEditViewHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalEditViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $journal = Journal::factory()->create([
            'name' => 'My Journal',
            'description' => 'My Journal Description',
        ]);
        Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'My Post',
            'written_at' => '2020-01-01',
        ]);

        $array = JournalEditViewHelper::data($journal->vault, $journal);
        $this->assertCount(4, $array);
        $this->assertEquals(
            [
                'id' => $journal->id,
                'name' => 'My Journal',
                'description' => 'My Journal Description',
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id,
                    'back' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id,
                ],
            ],
            $array
        );
    }
}
