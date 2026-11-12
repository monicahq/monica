<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannelEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelEnumTest extends TestCase
{
    #[Test]
    public function a_channel_asking_the_instance_for_nothing_is_always_configured(): void
    {
        config(['vonage.api_key' => null, 'vonage.api_secret' => null, 'vonage.sms_from' => null]);

        foreach ([NotificationChannelEnum::InApp, NotificationChannelEnum::Email, NotificationChannelEnum::Telegram] as $channel) {
            $this->assertTrue($channel->isConfiguredOnTheInstance());
        }
    }

    #[Test]
    public function a_text_message_is_configured_only_when_the_server_has_a_provider(): void
    {
        $this->assertTrue(NotificationChannelEnum::Sms->isConfiguredOnTheInstance());

        foreach (['vonage.api_key', 'vonage.api_secret', 'vonage.sms_from'] as $missing) {
            config([$missing => null]);
            $this->assertFalse(NotificationChannelEnum::Sms->isConfiguredOnTheInstance(), "{$missing} alone should be enough to make it unavailable.");
            $this->refreshApplication();
        }
    }

    #[Test]
    public function a_channel_whose_integration_is_not_installed_is_unavailable(): void
    {
        config(['services.telegram.installed' => false, 'services.vonage.installed' => false]);

        $this->assertFalse(NotificationChannelEnum::Telegram->isConfiguredOnTheInstance());
        $this->assertFalse(NotificationChannelEnum::Sms->isConfiguredOnTheInstance());
    }

    #[Test]
    public function an_installed_integration_still_has_to_be_configured(): void
    {
        config(['services.telegram.installed' => true, 'services.telegram.token' => null]);

        $this->assertFalse(NotificationChannelEnum::Telegram->isConfiguredOnTheInstance());
    }
}
