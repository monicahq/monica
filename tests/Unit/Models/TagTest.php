<?php

namespace Tests\Unit\Models;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TagTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $tag = Tag::factory()->create();

        $this->assertTrue($tag->vault()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $post = Post::factory()->create([]);
        $tag = Tag::factory()->create();

        $tag->posts()->sync([$post->id]);

        $this->assertTrue($tag->posts()->exists());
    }
}
