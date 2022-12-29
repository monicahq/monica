<?php

namespace Tests\Unit\Models;

use App\Models\Journal;
use App\Models\Post;
use App\Models\SliceOfLife;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $journal = Journal::factory()->create();

        $this->assertTrue($journal->vault()->exists());
    }

    /** @test */
    public function it_has_many_posts(): void
    {
        $journal = Journal::factory()->create();
        Post::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->assertTrue($journal->posts()->exists());
    }

    /** @test */
    public function it_has_many_slices_of_life(): void
    {
        $journal = Journal::factory()->create();
        SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
        ]);

        $this->assertTrue($journal->slicesOfLife()->exists());
    }
}
