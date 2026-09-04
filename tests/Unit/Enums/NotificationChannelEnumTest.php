<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannelEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelEnumTest extends TestCase
{
    #[Test]
    public function it_names_the_preference_column_that_switches_each_channel_on(): void
    {
        $this->assertSame('in_app_is_enabled', NotificationChannelEnum::InApp->preferenceColumn());
        $this->assertSame('email_is_enabled', NotificationChannelEnum::Email->preferenceColumn());
        $this->assertSame('push_is_enabled', NotificationChannelEnum::Push->preferenceColumn());
    }

    #[Test]
    public function it_says_that_push_has_nothing_behind_it_yet(): void
    {
        $this->assertTrue(NotificationChannelEnum::InApp->isDeliverable());
        $this->assertTrue(NotificationChannelEnum::Email->isDeliverable());
        $this->assertFalse(NotificationChannelEnum::Push->isDeliverable());
    }

    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals(['in_app', 'email', 'push'], NotificationChannelEnum::values());
    }
}
