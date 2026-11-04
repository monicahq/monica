<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MarkFullAccessOfferAsDismissed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkFullAccessOfferAsDismissedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_moment_somebody_hid_the_offer(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Pam',
        ]);

        $returned = new MarkFullAccessOfferAsDismissed(
            user: $user,
        )->execute();

        $this->assertInstanceOf(User::class, $returned);
        $this->assertEqualsWithDelta(now()->timestamp, $user->refresh()->full_access_offer_dismissed_at->timestamp, 5);
    }

    #[Test]
    public function it_keeps_the_first_moment_when_somebody_hides_the_offer_again(): void
    {
        $firstDismissal = now()->subMonth()->startOfSecond();

        $user = User::factory()->create([
            'first_name' => 'Jim',
            'full_access_offer_dismissed_at' => $firstDismissal,
        ]);

        new MarkFullAccessOfferAsDismissed(
            user: $user,
        )->execute();

        $this->assertEquals($firstDismissal, $user->refresh()->full_access_offer_dismissed_at);
    }
}
