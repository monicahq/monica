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
