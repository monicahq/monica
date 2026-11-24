<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Enums\SupportCategoryEnum;
use App\Models\User;
use App\Notifications\SupportRequestOpened;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestOpenedTest extends TestCase
{
    #[Test]
    public function it_tells_an_administrator_that_a_new_request_was_opened(): void
    {
        $notification = new SupportRequestOpened(
            reference: 'MON-482913',
            subject: 'October invoice shows the wrong company name',
            category: SupportCategoryEnum::Billing,
            accountId: 42,
            authorName: 'Michael Scott',
            body: 'The invoice still says Dunder Mifflin Paper Company.',
            hasAttachments: false,
            url: 'https://example.com/admin/support/1',
        );

        $message = $notification->toMail(User::factory()->make(['account_id' => 1]));
        $rendered = (string) $message->render();

        $this->assertEquals('New support request MON-482913', $message->subject);
        $this->assertEquals('https://example.com/admin/support/1', $message->actionUrl);
        $this->assertEquals(['Replying to this email does not reach Michael Scott. Answer from the request in the admin space.'], $message->outroLines);
        $this->assertStringContainsString('A new request was opened', $rendered);
        $this->assertStringContainsString('Category: Billing', $rendered);
        $this->assertStringContainsString('From Michael Scott, account 42', $rendered);
        $this->assertStringContainsString('The invoice still says Dunder Mifflin Paper Company.', $rendered);
        $this->assertStringNotContainsString('Files are attached.', $rendered);
        $this->assertEquals(EmailType::SupportRequestOpened, $notification->emailType());
        $this->assertEquals('high', $notification->queue);
    }

    #[Test]
    public function it_says_files_are_attached_when_the_request_carries_some(): void
    {
        $notification = new SupportRequestOpened(
            reference: 'MON-482913',
            subject: 'Import keeps timing out',
            category: SupportCategoryEnum::Technical,
            accountId: 42,
            authorName: 'Dwight Schrute',
            body: 'Here is the screen I get every time.',
            hasAttachments: true,
            url: 'https://example.com/admin/support/1',
        );

        $rendered = (string) $notification->toMail(User::factory()->make(['account_id' => 1]))->render();

        $this->assertStringContainsString('Files are attached. Open the request to see them.', $rendered);
    }

    #[Test]
    public function it_prints_a_link_the_user_wrote_as_text(): void
    {
        $notification = new SupportRequestOpened(
            reference: 'MON-482913',
            subject: '[Urgent](https://dundermifflin.example/subject)',
            category: SupportCategoryEnum::Billing,
            accountId: 42,
            authorName: 'Todd Packer',
            body: 'Please [sign in again](https://dundermifflin.example/login) and ![logo](https://dundermifflin.example/logo.png) <script>alert(1)</script>',
            hasAttachments: false,
            url: 'https://example.com/admin/support/1',
        );

        $rendered = (string) $notification->toMail(User::factory()->make(['account_id' => 1]))->render();

        $this->assertStringNotContainsString('href="https://dundermifflin.example', $rendered);
        $this->assertStringNotContainsString('src="https://dundermifflin.example', $rendered);
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('[sign in again](https://dundermifflin.example/login)', $rendered);
        $this->assertStringContainsString('href="https://example.com/admin/support/1"', $rendered);
    }

    #[Test]
    public function it_names_the_category_in_the_language_of_the_administrator(): void
    {
        app()->setLocale('fr_FR');

        $notification = new SupportRequestOpened(
            reference: 'MON-482913',
            subject: 'October invoice shows the wrong company name',
            category: SupportCategoryEnum::Billing,
            accountId: 42,
            authorName: 'Michael Scott',
            body: 'The invoice still says Dunder Mifflin Paper Company.',
            hasAttachments: false,
            url: 'https://example.com/admin/support/1',
        );

        $rendered = (string) $notification->toMail(User::factory()->make(['account_id' => 1]))->render();

        $this->assertStringContainsString('Facturation', $rendered);
        $this->assertStringContainsString('The invoice still says Dunder Mifflin Paper Company.', $rendered);
    }
}
