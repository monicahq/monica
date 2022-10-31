<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PostHelper;
use App\Models\Post;
use App\Models\PostSection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_statistics(): void
    {
        $post = Post::factory()->create();
        PostSection::factory()->count(2)->create([
            'post_id' => $post->id,
            'content' => 'Proident cupidatat ipsum esse Lorem Lorem consequat qui dolore aliqua voluptate occaecat.',
        ]);

        $this->assertEquals(
            [
                'word_count' => 24,
                'time_to_read_in_minute' => 1,
                'view_count' => 0,
            ],
            PostHelper::statistics($post)
        );
    }
}
