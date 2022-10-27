<?php

namespace Tests\Unit\Models;

use App\Models\Post;
use App\Models\PostSection;
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
    public function it_has_many_post_sections()
    {
        $post = Post::factory()->create();
        PostSection::factory(2)->create([
            'post_id' => $post->id,
        ]);

        $this->assertTrue($post->postSections()->exists());
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
}
