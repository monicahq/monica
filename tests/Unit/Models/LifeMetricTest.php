<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\LifeMetric;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LifeMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $lifeMetric = LifeMetric::factory()->create();

        $this->assertTrue($lifeMetric->vault()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $ross = Contact::factory()->create();
        $lifeMetric = LifeMetric::factory()->create();

        $ross->lifeMetrics()->sync([$lifeMetric->id]);

        $this->assertTrue($ross->lifeMetrics()->exists());
    }
}
