<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\User;
use App\Models\Vault;
use App\Vault\ManageJournals\Web\ViewHelpers\PostShowViewHelper;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'this is a title',
            'written_at' => '2022-01-01 00:00:00',
        ]);
        $section = PostSection::factory()->create([
            'post_id' => $post->id,
            'label' => 'super',
            'content' => 'this is a content',
        ]);

        $array = PostShowViewHelper::data($post, $user);

        $this->assertCount(8, $array);
        $this->assertEquals(
            $post->id,
            $array['id']
        );
        $this->assertEquals(
            'this is a title',
            $array['title']
        );
        $this->assertTrue(
            $array['title_exists']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $section->id,
                    'label' => 'super',
                    'content' => 'this is a content',
                ],
            ],
            $array['sections']->toArray()
        );
        $this->assertEquals(
            [
                'name' => $journal->name,
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                ],
            ],
            $array['journal']
        );
        $this->assertEquals(
            [
                'edit' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/edit',
                'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
            ],
            $array['url']
        );
    }
}
