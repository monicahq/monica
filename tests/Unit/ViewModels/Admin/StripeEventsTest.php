<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Models\StripeEvent;
use App\ViewModels\Admin\StripeEvents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripeEventsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_counts_each_kind_over_seven_and_thirty_days(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        StripeEvent::factory()->create(['type' => 'invoice.paid', 'created_at' => now()->subDays(2)]);
        StripeEvent::factory()->create(['type' => 'invoice.paid', 'created_at' => now()->subDays(10)]);
        StripeEvent::factory()->create(['type' => 'invoice.paid', 'created_at' => now()->subDays(40)]);
        StripeEvent::factory()->create(['type' => 'checkout.session.completed', 'created_at' => now()->subDays(20)]);

        $this->assertSame([
            ['kind' => 'checkout.session.completed', 'lastSevenDays' => 0, 'lastThirtyDays' => 1],
            ['kind' => 'invoice.paid', 'lastSevenDays' => 1, 'lastThirtyDays' => 2],
        ], new StripeEvents()->countsByKind());
    }

    #[Test]
    public function it_names_the_most_recent_confirmation_and_its_kind(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        StripeEvent::factory()->create(['type' => 'invoice.paid', 'created_at' => now()->subDays(3)]);
        StripeEvent::factory()->create(['type' => 'charge.refunded', 'created_at' => now()->subDay()]);

        $latest = new StripeEvents()->latest();

        $this->assertSame('charge.refunded', $latest['kind'] ?? null);
        $this->assertEquals(now()->subDay(), $latest['receivedAt'] ?? null);
    }

    #[Test]
    public function it_says_none_arrived_without_claiming_any_was_lost(): void
    {
        StripeEvent::factory()->create(['type' => 'invoice.paid', 'created_at' => now()->subDays(40)]);

        $confirmations = new StripeEvents;

        $this->assertTrue($confirmations->isEmpty());
        $this->assertSame([], $confirmations->countsByKind());
    }
}
