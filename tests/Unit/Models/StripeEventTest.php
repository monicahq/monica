<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_prunes_the_events_older_than_the_retention(): void
    {
        $old = StripeEvent::factory()->create(['created_at' => now()->subDays(31)]);
        $recent = StripeEvent::factory()->create(['created_at' => now()->subDays(29)]);

        $this->artisan('model:prune', ['--model' => [StripeEvent::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
