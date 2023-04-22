<?php

namespace Tests\Unit\Models;

use App\Models\JournalMetric;
use App\Models\PostMetric;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_journal()
    {
        $journalMetric = JournalMetric::factory()->create();

        $this->assertTrue($journalMetric->journal()->exists());
    }

    /** @test */
    public function it_has_many_post_metrics(): void
    {
        $journalMetric = JournalMetric::factory()->create();
        PostMetric::factory()->create(['journal_metric_id' => $journalMetric->id]);

        $this->assertTrue($journalMetric->postMetrics()->exists());
    }
}
