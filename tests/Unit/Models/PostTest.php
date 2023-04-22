<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\File;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\PostSection;
use App\Models\SliceOfLife;
use App\Models\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_journal()
    {
        $post = Post::factory()->create();

        $this->assertTrue($post->journal()->exists());
    }

    /** @test */
    public function it_has_one_slice_of_life()
    {
        $post = Post::factory()->create([
            'slice_of_life_id' => SliceOfLife::factory()->create()->id,
        ]);

        $this->assertTrue($post->sliceOfLife()->exists());
    }

    /** @test */
    public function it_has_many_post_sections()
    {
        $post = Post::factory()->create();
        PostSection::factory(2)->create([
            'post_id' => $post->id,
        ]);

        $this->assertTrue($post->postSections()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $ross = Contact::factory()->create([]);
        $post = Post::factory()->create();

        $post->contacts()->sync([$ross->id]);

        $this->assertTrue($post->contacts()->exists());
    }

    /** @test */
    public function it_has_many_tags(): void
    {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();

        $post->tags()->sync([$tag->id]);

        $this->assertTrue($post->tags()->exists());
    }

    /** @test */
    public function it_has_many_files(): void
    {
        $post = Post::factory()->create();
        File::factory()->count(2)->create([
            'fileable_id' => $post->id,
            'fileable_type' => Post::class,
        ]);

        $this->assertTrue($post->files()->exists());
    }

    /** @test */
    public function it_has_many_post_metrics(): void
    {
        $post = Post::factory()->create();
        PostMetric::factory()->count(2)->create([
            'post_id' => $post->id,
        ]);

        $this->assertTrue($post->postMetrics()->exists());
    }

    /** @test */
    public function it_gets_the_title(): void
    {
        $post = Post::factory()->create([
            'title' => null,
        ]);

        $this->assertEquals(
            'Undefined',
            $post->title
        );

        $post = Post::factory()->create([
            'title' => 'Awesome post',
        ]);

        $this->assertEquals(
            'Awesome post',
            $post->title
        );
    }

    /** @test */
    public function it_gets_the_excerpt(): void
    {
        $post = Post::factory()->create([
            'title' => null,
        ]);

        $this->assertNull(
            $post->excerpt
        );

        PostSection::factory()->create([
            'post_id' => $post->id,
            'content' => null,
        ]);

        PostSection::factory()->create([
            'post_id' => $post->id,
            'content' => 'this is incredible',
        ]);

        $this->assertEquals(
            'this is incredible',
            $post->excerpt
        );
    }
}
