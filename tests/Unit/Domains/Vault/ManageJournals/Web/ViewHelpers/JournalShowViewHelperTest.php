<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalShowViewHelper;
use App\Models\Journal;
use App\Models\Post;
use App\Models\SliceOfLife;
use App\Models\User;
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
        Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'My Post',
            'written_at' => '2020-01-01',
        ]);

        $array = JournalShowViewHelper::data($journal, 2020, $user);
        $this->assertCount(8, $array);
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
                'journal_metrics' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/metrics',
                'photo_index' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/photos',
                'edit' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/edit',
                'destroy' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id,
                'create' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/posts/create',
                'slice_index' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/slices',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_posts_in_the_given_year(): void
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

        $collection = JournalShowViewHelper::postsInYear($journal, 2020, $user);

        $this->assertEquals(
            '12',
            $collection->toArray()[0]['id']
        );
        $this->assertEquals(
            'December 2020',
            $collection->toArray()[0]['month_human_format']
        );
        $this->assertEquals(
            'DEC',
            $collection->toArray()[0]['month']
        );
    }

    /** @test */
    public function it_gets_the_years_in_the_given_journal(): void
    {
        User::factory()->create();
        $journal = Journal::factory()->create([
            'name' => 'My Journal',
            'description' => 'My Journal Description',
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'My Post',
            'written_at' => '2020-01-01',
        ]);

        $collection = JournalShowViewHelper::yearsOfContentInJournal($journal);

        $this->assertEquals(
            [
                0 => [
                    'year' => 2020,
                    'posts' => 1,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/years/2020',
                    ],
                ],
            ],
            $collection->toArray()
        );
    }

    /** @test */
    public function it_gets_the_slices_of_life_in_the_journal(): void
    {
        User::factory()->create();
        $journal = Journal::factory()->create([
            'name' => 'My Journal',
            'description' => 'My Journal Description',
        ]);
        $slice = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'name of the slice of life',
        ]);

        $collection = JournalShowViewHelper::slices($journal);

        $this->assertEquals(
            [
                0 => [
                    'id' => $slice->id,
                    'name' => 'name of the slice of life',
                    'date_range' => null,
                    'cover_image' => null,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                    ],
                ],
            ],
            $collection->toArray()
        );
    }
}
