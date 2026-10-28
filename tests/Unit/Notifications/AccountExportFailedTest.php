<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\AccountExportFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountExportFailedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_there_is_nothing_to_download_and_points_at_the_screen_to_ask_again(): void
    {
        $angela = User::factory()->create();

        $message = new AccountExportFailed(url: 'https://monica.test/settings/account/imports')->toMail($angela);

        $this->assertSame('Your export failed', $message->subject);
        $this->assertSame('https://monica.test/settings/account/imports', $message->actionUrl);
        $this->assertStringContainsString('ask for a new export', (string) $message->render());
    }

    #[Test]
    public function it_is_recorded_as_the_email_saying_an_export_failed(): void
    {
        $notification = new AccountExportFailed(url: 'https://monica.test');

        $this->assertSame(EmailType::AccountExportFailed, $notification->emailType());
        $this->assertSame('high', $notification->queue);
    }
}
