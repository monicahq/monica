<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\AccountExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountExportReadyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_points_at_the_screen_the_file_is_downloaded_from_and_warns_what_it_holds(): void
    {
        $pam = User::factory()->create();

        $message = new AccountExportReady(url: 'https://monica.test/settings/account/imports')->toMail($pam);

        $this->assertSame('Your export is ready', $message->subject);
        $this->assertSame('https://monica.test/settings/account/imports', $message->actionUrl);
        $this->assertStringContainsString('sensitive fields included', (string) $message->panel);
        $this->assertStringContainsString('seven days', (string) $message->render());
    }

    #[Test]
    public function it_is_recorded_as_the_email_saying_an_export_is_ready(): void
    {
        $notification = new AccountExportReady(url: 'https://monica.test');

        $this->assertSame(EmailType::AccountExportReady, $notification->emailType());
        $this->assertSame('high', $notification->queue);
    }
}
