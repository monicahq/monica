<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\SupportReplySent;
use Illuminate\Mail\Markdown;
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

    #[Test]
    public function nothing_in_the_reply_can_run_or_load_anything_in_the_email(): void
    {
        $body = "Dwight says <script>alert(1)</script>\n\n![beets](https://example.com/pixel.gif) [farm](javascript:alert(1))";
        $notification = new SupportReplySent(
            reference: 'MON-482913',
            subject: 'Schrute Farms',
            administratorName: 'Jim Halpert',
            body: $body,
            url: 'https://example.com/support/requests/1',
        );

        $message = $notification->toMail(User::factory()->make(['account_id' => 1]));
        $html = (string) $message->render();
        $text = (string) app(Markdown::class)->renderText($message->markdown, $message->data());

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('src="https://example.com/pixel.gif"', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringContainsString('Dwight says <script>alert(1)</script>', $text);
    }
}
