<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\AccountImportEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportEndedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_tells_how_much_came_over_and_points_at_the_report(): void
    {
        $michael = User::factory()->create();

        $message = new AccountImportEnded(imported: 1767, leftOut: 3, rolledBack: false, url: 'https://monica.test/settings/account/imports/dundies')->toMail($michael);

        $this->assertSame('Your import is finished', $message->subject);
        $this->assertSame('https://monica.test/settings/account/imports/dundies', $message->actionUrl);
        $this->assertStringContainsString('1767 items came over.', (string) $message->render());
        $this->assertStringContainsString('3 items did not come over', (string) $message->render());
    }

    #[Test]
    public function it_says_nothing_was_kept_when_the_import_was_undone(): void
    {
        $michael = User::factory()->create();

        $message = new AccountImportEnded(imported: 0, leftOut: 0, rolledBack: true, url: 'https://monica.test/settings/account/imports/dundies')->toMail($michael);

        $this->assertSame('Your import was undone', $message->subject);
        $this->assertStringContainsString('as it was before the import started', (string) $message->render());
    }

    #[Test]
    public function it_is_recorded_as_the_email_saying_an_import_ended(): void
    {
        $notification = new AccountImportEnded(imported: 1, leftOut: 0, rolledBack: false, url: 'https://monica.test');

        $this->assertSame(EmailType::AccountImportEnded, $notification->emailType());
        $this->assertSame('high', $notification->queue);
    }
}
