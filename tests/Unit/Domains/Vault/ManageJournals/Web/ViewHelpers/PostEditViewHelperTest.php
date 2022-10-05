<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\Vault;
use App\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostEditViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $section = PostSection::factory()->create([
            'post_id' => $post->id,
        ]);

        $array = PostEditViewHelper::data($journal, $post);

        $this->assertCount(6, $array);
        $this->assertEquals(
            $post->id,
            $array['id']
        );
        $this->assertEquals(
            $post->title,
            $array['title']
        );
        $this->assertEquals(
            [
                'name' => $journal->name,
            ],
            $array['journal']
        );
        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/update',
                'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
            ],
            $array['url']
        );
    }
}
