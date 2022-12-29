<?php

namespace Tests\Unit\Helpers;

use App\Helpers\SliceOfLifeHelper;
use App\Models\Post;
use App\Models\SliceOfLife;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SliceOfLifeHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_oldest_and_newest_post_in_the_slice(): void
    {
        $sliceOfLife = SliceOfLife::factory()->create();
        $oldestPost = Post::factory()->create([
            'slice_of_life_id' => $sliceOfLife->id,
            'written_at' => '1982-01-01',
        ]);
        $newestPost = Post::factory()->create([
            'slice_of_life_id' => $sliceOfLife->id,
            'written_at' => '2020-01-31',
        ]);

        $this->assertEquals(
            'Jan 01, 1982 - Jan 31, 2020',
            SliceOfLifeHelper::getDateRange($sliceOfLife)
        );
    }

    /** @test */
    public function it_returns_null_if_there_are_no_posts_in_the_slice(): void
    {
        $sliceOfLife = SliceOfLife::factory()->create();
        $this->assertNull(
            SliceOfLifeHelper::getDateRange($sliceOfLife)
        );
    }
}
