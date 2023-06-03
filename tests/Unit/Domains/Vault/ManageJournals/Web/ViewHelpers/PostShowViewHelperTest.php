<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostShowViewHelper;
use App\Models\File;
use App\Models\Journal;
use App\Models\JournalMetric;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\PostSection;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

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
        $previousPost = Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'this is a title',
            'written_at' => '2010-01-01 00:00:00',
        ]);
        $section = PostSection::factory()->create([
            'post_id' => $post->id,
            'label' => 'super',
            'content' => '# this is a content',
        ]);
        $tag = Tag::factory()->create([
            'name' => 'super',
        ]);
        $post->tags()->attach($tag->id);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
            'size' => 123,
            'type' => File::TYPE_PHOTO,
        ]);
        $file->fileable_id = $post->id;
        $file->fileable_type = Post::class;
        $file->save();

        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);
        PostMetric::factory()->create([
            'post_id' => $post->id,
            'value' => 123,
            'label' => 'label',
            'journal_metric_id' => $journalMetric->id,
        ]);

        $array = PostShowViewHelper::data($post, $user);

        $this->assertCount(16, $array);
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
            $section->id,
            $array['sections']->toArray()[0]['id']
        );
        $this->assertEquals(
            'super',
            $array['sections']->toArray()[0]['label']
        );
        $this->assertEquals(
            '<h1>this is a content</h1>',
            trim($array['sections']->toArray()[0]['content'])
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $tag->id,
                    'name' => 'super',
                ],
            ],
            $array['tags']->toArray()
        );
        $this->assertEquals(
            [
                'id' => $previousPost->id,
                'title' => $previousPost->title,
                'title_exists' => true,
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$previousPost->id,
                ],
            ],
            $array['previousPost']
        );
        $this->assertNull(
            $array['nextPost']
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
                0 => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => [
                        'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/100x100/smart/-/format/auto/-/quality/smart_retina/',
                    ],
                ],
            ],
            $array['photos']->toArray()
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
