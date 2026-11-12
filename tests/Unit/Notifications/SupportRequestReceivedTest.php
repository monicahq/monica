<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SupportRequestReceived;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestReceivedTest extends TestCase
{
    #[Test]
    public function it_sends_the_user_a_copy_of_what_they_asked(): void
    {
        $notification = new SupportRequestReceived(
            reference: 'MON-482913',
            subject: 'October invoice shows the wrong company name',
            body: 'The invoice still says Dunder Mifflin Paper Company.',
            url: 'https://example.com/support/requests/1',
        );

        $message = $notification->toMail(User::factory()->make(['account_id' => 1]));

        $this->assertEquals('We have your request MON-482913', $message->subject);
        $this->assertEquals('https://example.com/support/requests/1', $message->actionUrl);
        $this->assertEquals(['Replying to this email does not reach us. To add something, write from the request page.'], $message->outroLines);
        $this->assertStringContainsString('The invoice still says Dunder Mifflin Paper Company.', (string) $message->render());
        $this->assertEquals(EmailType::SupportRequestReceived, $notification->emailType());
        $this->assertEquals('high', $notification->queue);
    }
}
