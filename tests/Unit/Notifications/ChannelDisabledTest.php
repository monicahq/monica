<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\ChannelDisabled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChannelDisabledTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_channel_that_was_switched_off(): void
    {
        $message = $this->notification()->toMail(User::factory()->create());

        $this->assertSame('Slack has been switched off', $message->subject);
    }

    #[Test]
    public function it_says_why_and_that_nothing_was_lost(): void
    {
        $message = $this->notification()->toMail(User::factory()->create());

        $this->assertContains(
            'Nothing we sent there got through, several times in a row, so we have stopped trying.',
            $message->introLines,
        );
        $this->assertContains(
            'What came back was: The token has been revoked.',
            $message->introLines,
        );
        $this->assertContains(
            'Nothing has been lost. Put the settings right and switch it back on, and it will carry things again.',
            $message->introLines,
        );
    }

    #[Test]
    public function it_goes_out_by_email_rather_than_on_the_channel_that_stopped_working(): void
    {
        $this->assertSame(['mail'], $this->notification()->via(User::factory()->create()));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_channel_switched_off(): void
    {
        $this->assertEquals(EmailType::ChannelDisabled, $this->notification()->emailType());
    }

    private function notification(): ChannelDisabled
    {
        return new ChannelDisabled(
            channelName: 'Slack',
            reason: 'The token has been revoked.',
        );
    }
}
