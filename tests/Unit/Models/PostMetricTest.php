<?php

namespace Tests\Unit\Models;

use App\Models\PostMetric;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_journal_metric()
    {
        $postMetric = PostMetric::factory()->create();

        $this->assertTrue($postMetric->journalMetric()->exists());
    }

    /** @test */
    public function it_has_one_post()
    {
        $postMetric = PostMetric::factory()->create();

        $this->assertTrue($postMetric->post()->exists());
    }
}
