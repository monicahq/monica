<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SupportReplySent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportReplySentTest extends TestCase
{
    #[Test]
    public function it_brings_the_reply_of_the_support_team_to_the_user(): void
    {
        $notification = new SupportReplySent(
            reference: 'MON-482913',
            subject: 'October invoice shows the wrong company name',
            administratorName: 'Jim Halpert',
            body: 'We changed it to Michael Scott Paper Company.',
            url: 'https://example.com/support/requests/1',
        );

        $message = $notification->toMail(User::factory()->make(['account_id' => 1]));

        $this->assertEquals('Reply to your request MON-482913', $message->subject);
        $this->assertEquals('https://example.com/support/requests/1', $message->actionUrl);
        $this->assertEquals(['Replying to this email does not reach us. To answer, write from the request page.'], $message->outroLines);
        $this->assertStringContainsString('We changed it to Michael Scott Paper Company.', (string) $message->render());
        $this->assertEquals(EmailType::SupportReplySent, $notification->emailType());
    }
}
