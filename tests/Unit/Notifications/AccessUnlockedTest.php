<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\AccessKindEnum;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\AccessUnlocked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessUnlockedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_reader_to_their_billing_screen(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Lifetime)->toMail($michael);

        $this->assertEquals(route('settings.billing.index'), $message->actionUrl);
    }

    #[Test]
    public function it_goes_out_as_an_email_on_the_high_queue(): void
    {
        $michael = User::factory()->create();
        $notification = new AccessUnlocked(kind: AccessKindEnum::Lifetime);

        $this->assertEquals(['mail'], $notification->via($michael));
        $this->assertEquals('high', $notification->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_access_that_was_unlocked(): void
    {
        $this->assertEquals(EmailType::AccessUnlocked, new AccessUnlocked(kind: AccessKindEnum::Lifetime)->emailType());
    }
}
