<?php

namespace Tests\Unit\Models;

use App\Models\Journal;
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
}
