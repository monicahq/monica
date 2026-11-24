<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SupportMessageAdded;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportMessageAddedTest extends TestCase
{
    #[Test]
    public function it_tells_an_administrator_that_a_user_wrote_again(): void
    {
        $notification = new SupportMessageAdded(
            reference: 'MON-245110',
            subject: 'Wrong time zone on reminders',
            accountId: 42,
            authorName: 'Michael Scott',
            body: 'Settings show New York. I moved to Paris in March.',
            hasAttachments: false,
            reopened: false,
            url: 'https://example.com/admin/support/1',
        );

        $message = $notification->toMail(User::factory()->make(['account_id' => 1]));
        $rendered = (string) $message->render();

        $this->assertEquals('New message in support request MON-245110', $message->subject);
        $this->assertEquals('https://example.com/admin/support/1', $message->actionUrl);
        $this->assertEquals(['Replying to this email does not reach Michael Scott. Answer from the request in the admin space.'], $message->outroLines);
        $this->assertStringContainsString('A user wrote again in an existing request', $rendered);
        $this->assertStringContainsString('From Michael Scott, account 42', $rendered);
        $this->assertStringContainsString('Settings show New York. I moved to Paris in March.', $rendered);
        $this->assertStringNotContainsString('This message reopened the request', $rendered);
        $this->assertStringNotContainsString('Files are attached.', $rendered);
        $this->assertEquals(EmailType::SupportMessageAdded, $notification->emailType());
        $this->assertEquals('high', $notification->queue);
    }

    #[Test]
    public function it_says_the_message_reopened_a_solved_request_and_carries_files(): void
    {
        $notification = new SupportMessageAdded(
            reference: 'MON-245110',
            subject: 'Holly sees the whole vault',
            accountId: 42,
            authorName: 'Pam Beesly',
            body: 'Holly still sees the whole vault.',
            hasAttachments: true,
            reopened: true,
            url: 'https://example.com/admin/support/1',
        );

        $rendered = (string) $notification->toMail(User::factory()->make(['account_id' => 1]))->render();

        $this->assertStringContainsString('This message reopened the request, which was solved.', $rendered);
        $this->assertStringContainsString('Files are attached. Open the request to see them.', $rendered);
    }
}
