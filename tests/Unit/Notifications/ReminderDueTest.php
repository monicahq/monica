<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Enums\NotificationChannelEnum;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ReminderDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderDueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_its_subject_from_what_the_reminder_says(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertEquals('Birthday, Michael Scott', $message->subject);
    }

    #[Test]
    public function it_names_the_record_the_date_and_how_far_off_it_is(): void
    {
        $michael = User::factory()->create();

        $rendered = (string) $this->notification()->toMail($michael)->render();

        $this->assertStringContainsString('Birthday, Michael Scott', $rendered);
        $this->assertStringContainsString('15 March 2027', $rendered);
        $this->assertStringContainsString('It is in seven days.', $rendered);
    }

    #[Test]
    public function it_says_which_date_it_is_about_rather_than_todays(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertContains(
            'It is about Michael Scott, and the date it reads is 15 March 2027.',
            $message->introLines,
        );
    }

    #[Test]
    public function it_says_where_the_reminder_can_be_changed(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertContains(
            'You asked to be told this when you added the reminder. You can change it, or stop it, wherever that date is written down.',
            $message->introLines,
        );
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], $this->notification()->via($michael));
    }

    #[Test]
    public function it_goes_on_the_queue_a_deadline_cannot_wait_on(): void
    {
        $this->assertEquals('high', $this->notification()->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_reminder_that_came_due(): void
    {
        $this->assertEquals(EmailType::ReminderDue, $this->notification()->emailType());
    }

    private function notification(NotificationChannelEnum $channel = NotificationChannelEnum::Email): ReminderDue
    {
        return new ReminderDue(
            reminderName: 'Birthday, Michael Scott',
            recordName: 'Michael Scott',
            sourceDate: '15 March 2027',
            whenItIsAbout: 'It is in seven days.',
            channel: $this->channel($channel),
        );
    }

    private function channel(NotificationChannelEnum $channel): NotificationChannel
    {
        return NotificationChannel::factory()->channel($channel)->enabled()->make();
    }

    #[Test]
    public function it_says_the_same_thing_on_telegram_in_one_plain_sentence(): void
    {
        $message = $this->notification(NotificationChannelEnum::Telegram)->toTelegram(User::factory()->make());

        $text = (string) $message->toArray()['text'];

        $this->assertStringContainsString('Birthday, Michael Scott', $text);
        $this->assertStringContainsString('It is in seven days.', $text);
        $this->assertStringContainsString('Michael Scott', $text);
    }

    #[Test]
    public function it_carries_no_token_to_telegram_because_the_bot_is_the_instances(): void
    {
        $message = $this->notification(NotificationChannelEnum::Telegram)->toTelegram(User::factory()->make());

        $this->assertStringContainsString('Birthday, Michael Scott', (string) $message->toArray()['text']);
        $this->assertFalse($message->hasToken());
    }

    #[Test]
    public function a_text_message_is_shortened_to_what_the_channel_carries(): void
    {
        $message = $this->notification(NotificationChannelEnum::Sms)->toVonage(User::factory()->make());

        $this->assertLessThanOrEqual(140, mb_strlen($message->content));
        $this->assertStringContainsString('Birthday, Michael Scott', $message->content);
    }

    #[Test]
    public function a_text_message_carries_no_client_of_its_own_so_the_instances_is_used(): void
    {
        $message = $this->notification(NotificationChannelEnum::Sms)->toVonage(User::factory()->make());

        $this->assertNull($message->client);
    }

    #[Test]
    public function a_text_message_names_who_it_was_for_so_a_provider_bill_can_be_read(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification(NotificationChannelEnum::Sms)->toVonage($michael);

        $this->assertSame((string) $michael->id, $message->clientReference);
    }
}
