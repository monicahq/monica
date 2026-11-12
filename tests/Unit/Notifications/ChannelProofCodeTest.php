<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Enums\NotificationChannelEnum;
use App\Models\NotificationChannel;
use App\Models\User;
use App\Notifications\ChannelProofCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelProofCodeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_code_where_it_cannot_be_missed(): void
    {
        $message = $this->notification()->toMail(User::factory()->create());

        $this->assertStringContainsString('123456', (string) $message->render());
    }

    #[Test]
    public function it_goes_out_on_the_channel_being_proved_rather_than_any_other(): void
    {
        $user = User::factory()->create();

        $this->assertSame(['vonage'], $this->notification()->via($user));
    }

    #[Test]
    public function it_names_the_channel_carrying_it(): void
    {
        $notification = $this->notification();

        $this->assertSame(NotificationChannelEnum::Sms, $notification->notificationChannel()->channel);
    }

    #[Test]
    public function it_goes_on_the_queue_that_cannot_be_kept_waiting(): void
    {
        $this->assertSame('high', $this->notification()->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_code(): void
    {
        $this->assertEquals(EmailType::ChannelProofCode, $this->notification()->emailType());
    }

    private function notification(): ChannelProofCode
    {
        return new ChannelProofCode(
            code: '123456',
            channel: NotificationChannel::factory()->channel(NotificationChannelEnum::Sms)->enabled()->make(),
        );
    }
}
