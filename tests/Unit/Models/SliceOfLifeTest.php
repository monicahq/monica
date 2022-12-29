<?php

namespace Tests\Unit\Models;

use App\Models\File;
use App\Models\Post;
use App\Models\SliceOfLife;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SliceOfLifeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_journal()
    {
        $sliceOfLife = SliceOfLife::factory()->create();

        $this->assertTrue($sliceOfLife->journal()->exists());
    }

    /** @test */
    public function it_has_many_posts(): void
    {
        $slice = SliceOfLife::factory()->create([]);
        Post::factory()->create([
            'slice_of_life_id' => $slice->id,
        ]);

        $this->assertTrue($slice->posts()->exists());
    }

    /** @test */
    public function it_has_one_file(): void
    {
        $slice = SliceOfLife::factory()->create([]);
        $file = File::factory()->create();
        $slice->file_cover_image_id = $file->id;
        $slice->save();

        $this->assertTrue($slice->file()->exists());
    }
}
