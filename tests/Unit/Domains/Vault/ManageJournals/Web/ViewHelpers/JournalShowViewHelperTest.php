<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\Post;
use App\Models\User;
use App\Vault\ManageJournals\Web\ViewHelpers\JournalShowViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalShowViewHelperTest extends TestCase
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
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'My Post',
            'written_at' => '2020-01-01',
        ]);

        $array = JournalShowViewHelper::data($journal, $user);
        $this->assertCount(4, $array);
        $this->assertEquals(
            $journal->id,
            $array['id']
        );
        $this->assertEquals(
            'My Journal',
            $array['name']
        );
        $this->assertEquals(
            'My Journal Description',
            $array['description']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $post->id,
                    'title' => 'My Post',
                    'written_at' => 'Jan 01, 2020',
                ],
            ],
            $array['posts']->toArray()
        );
    }
}
