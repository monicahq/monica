<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\NotificationChannelEnum;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $preference = NotificationPreference::factory()->create();

        $this->assertTrue($preference->account()->exists());
    }

    #[Test]
    public function it_is_reachable_from_the_account(): void
    {
        $preference = NotificationPreference::factory()->create();

        $this->assertTrue($preference->account->notificationPreference()->exists());
    }

    #[Test]
    public function it_allows_the_channels_that_are_switched_on(): void
    {
        $preference = NotificationPreference::factory()->create();

        $this->assertTrue($preference->allows(NotificationChannelEnum::InApp));
        $this->assertTrue($preference->allows(NotificationChannelEnum::Email));
    }

    #[Test]
    public function it_refuses_push_even_when_somebody_switches_it_on(): void
    {
        $preference = NotificationPreference::factory()->create(['push_is_enabled' => true]);

        $this->assertFalse($preference->allows(NotificationChannelEnum::Push));
    }

    #[Test]
    public function it_lists_the_channels_a_notification_is_made_for(): void
    {
        $preference = NotificationPreference::factory()->create();

        $this->assertEquals([
            NotificationChannelEnum::InApp,
            NotificationChannelEnum::Email,
        ], $preference->enabledChannels());
    }

    #[Test]
    public function it_allows_an_account_to_ask_for_silence(): void
    {
        $preference = NotificationPreference::factory()->silent()->create();

        $this->assertEquals([], $preference->enabledChannels());
    }

    #[Test]
    public function it_knows_whether_everything_is_folded_into_a_summary(): void
    {
        $immediate = NotificationPreference::factory()->create();
        $digest = NotificationPreference::factory()->digestAt()->create();

        $this->assertFalse($immediate->foldsIntoADigest());
        $this->assertTrue($digest->foldsIntoADigest());
    }
}
