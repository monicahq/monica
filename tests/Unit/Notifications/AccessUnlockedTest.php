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
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Lifetime)->toMail($michael);

        $this->assertEquals(
            'Your '.config('app.name').' account now has full access',
            $message->subject,
        );
    }

    #[Test]
    public function it_says_there_is_nothing_to_renew_after_the_one_time_purchase(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Lifetime)->toMail($michael);

        $this->assertStringContainsString('nothing to renew', (string) $message->render());
    }

    #[Test]
    public function it_says_how_long_a_subscription_holds_access(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Subscription)->toMail($michael);

        $this->assertStringContainsString('for as long as it runs', (string) $message->render());
        $this->assertStringContainsString('when it next renews', (string) $message->outroLines[0]);
    }

    #[Test]
    public function it_says_who_opened_access_when_an_administrator_did(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Granted)->toMail($michael);

        $this->assertStringContainsString('We have opened full access', (string) $message->render());
    }

    #[Test]
    public function it_sends_the_reader_to_their_billing_screen(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Lifetime)->toMail($michael);

        $this->assertEquals(route('settings.billing.index'), $message->actionUrl);
    }

    #[Test]
    public function it_says_the_receipt_comes_from_stripe(): void
    {
        $michael = User::factory()->create();

        $message = new AccessUnlocked(kind: AccessKindEnum::Lifetime)->toMail($michael);

        $this->assertStringContainsString('comes from Stripe', (string) $message->panel);
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
