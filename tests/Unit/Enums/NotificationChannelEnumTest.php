<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationChannelEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationChannelEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_channel(): void
    {
        $this->assertSame('In the app', NotificationChannelEnum::InApp->label());
        $this->assertSame('Email', NotificationChannelEnum::Email->label());
        $this->assertSame('Telegram', NotificationChannelEnum::Telegram->label());
        $this->assertSame('Text message', NotificationChannelEnum::Sms->label());
    }

    #[Test]
    public function it_names_each_channel_inside_a_sentence(): void
    {
        $this->assertSame('in the app', NotificationChannelEnum::InApp->deliveredPhrase());
        $this->assertSame('by email', NotificationChannelEnum::Email->deliveredPhrase());
        $this->assertSame('on Telegram', NotificationChannelEnum::Telegram->deliveredPhrase());
        $this->assertSame('by text message', NotificationChannelEnum::Sms->deliveredPhrase());
    }

    #[Test]
    public function it_says_in_a_full_sentence_where_something_went(): void
    {
        $this->assertSame('It appeared in the application.', NotificationChannelEnum::InApp->deliveredSentence());
        $this->assertSame('It went out by email.', NotificationChannelEnum::Email->deliveredSentence());
        $this->assertSame('It went out on Telegram.', NotificationChannelEnum::Telegram->deliveredSentence());
        $this->assertSame('It went out by text message.', NotificationChannelEnum::Sms->deliveredSentence());
    }

    #[Test]
    public function it_says_which_channels_the_account_has_to_supply_a_destination_for(): void
    {
        $this->assertFalse(NotificationChannelEnum::InApp->needsDestination());
        $this->assertFalse(NotificationChannelEnum::Email->needsDestination());
        $this->assertTrue(NotificationChannelEnum::Telegram->needsDestination());
        $this->assertTrue(NotificationChannelEnum::Sms->needsDestination());
    }

    #[Test]
    public function only_telegram_is_handed_its_own_destination(): void
    {
        foreach ([NotificationChannelEnum::InApp, NotificationChannelEnum::Email, NotificationChannelEnum::Sms] as $channel) {
            $this->assertFalse($channel->discoversDestination());
        }
    }

    #[Test]
    public function a_text_message_asks_the_account_for_no_credential_because_the_provider_is_the_instances(): void
    {
        $this->assertTrue(NotificationChannelEnum::Sms->needsDestination());
        $this->assertTrue(NotificationChannelEnum::Sms->provesSilently());
    }

    #[Test]
    public function only_the_text_message_accepts_a_wrong_destination_without_complaining(): void
    {
        $this->assertTrue(NotificationChannelEnum::Sms->provesSilently());

        foreach ([NotificationChannelEnum::InApp, NotificationChannelEnum::Email, NotificationChannelEnum::Telegram] as $channel) {
            $this->assertFalse($channel->provesSilently());
        }
    }

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
    public function a_channel_needing_no_integration_is_unaffected_by_one_being_absent(): void
    {
        config(['services.telegram.installed' => false, 'services.vonage.installed' => false]);

        $this->assertTrue(NotificationChannelEnum::InApp->isConfiguredOnTheInstance());
        $this->assertTrue(NotificationChannelEnum::Email->isConfiguredOnTheInstance());
    }

    #[Test]
    public function an_installed_integration_still_has_to_be_configured(): void
    {
        config(['services.telegram.installed' => true, 'services.telegram.token' => null]);

        $this->assertFalse(NotificationChannelEnum::Telegram->isConfiguredOnTheInstance());
    }

    #[Test]
    public function it_says_how_long_a_message_may_be(): void
    {
        $this->assertNull(NotificationChannelEnum::InApp->maximumLength());
        $this->assertNull(NotificationChannelEnum::Email->maximumLength());
        $this->assertSame(4096, NotificationChannelEnum::Telegram->maximumLength());
        $this->assertSame(140, NotificationChannelEnum::Sms->maximumLength());
    }

    #[Test]
    public function it_names_the_laravel_channel_that_carries_it(): void
    {
        $this->assertNull(NotificationChannelEnum::InApp->laravelChannel());
        $this->assertSame('mail', NotificationChannelEnum::Email->laravelChannel());
        $this->assertSame('telegram', NotificationChannelEnum::Telegram->laravelChannel());
        $this->assertSame('vonage', NotificationChannelEnum::Sms->laravelChannel());
    }

    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals(['in_app', 'email', 'telegram', 'sms'], NotificationChannelEnum::values());
    }
}
