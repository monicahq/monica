<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\AccessEndReasonEnum;
use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\AccessEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessEndedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_it_is_about_in_the_subject(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::Cancelled)->toMail($michael);

        $this->assertEquals(
            'Your '.config('app.name').' account is back on the free plan',
            $message->subject,
        );
    }

    #[Test]
    public function it_gives_every_reason_its_own_wording(): void
    {
        $michael = User::factory()->create();

        $wordings = [];

        foreach (AccessEndReasonEnum::cases() as $reason) {
            $rendered = (string) new AccessEnded(reason: $reason)->toMail($michael)->render();

            $this->assertStringContainsString('back on the free plan', $rendered);

            $wordings[] = $rendered;
        }

        $this->assertCount(count(AccessEndReasonEnum::cases()), array_unique($wordings));
    }

    #[Test]
    public function it_says_the_payment_was_refunded(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::Refunded)->toMail($michael);

        $this->assertStringContainsString('was refunded', (string) $message->render());
    }

    #[Test]
    public function it_says_the_payment_was_disputed_with_the_bank(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::Disputed)->toMail($michael);

        $this->assertStringContainsString('disputed with your bank', (string) $message->render());
    }

    #[Test]
    public function it_says_the_payment_could_not_be_taken(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::PaymentFailed)->toMail($michael);

        $this->assertStringContainsString('could not be taken', (string) $message->render());
    }

    #[Test]
    public function it_promises_that_nothing_was_deleted(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::Cancelled)->toMail($michael);

        $this->assertStringContainsString('Nothing was deleted', (string) $message->render());
    }

    #[Test]
    public function it_sends_the_reader_to_the_offers(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::Cancelled)->toMail($michael);

        $this->assertEquals(route('settings.billing.index'), $message->actionUrl);
        $this->assertStringContainsString('can be taken up again', (string) $message->outroLines[0]);
    }

    #[Test]
    public function it_invites_a_reply_if_this_comes_as_a_surprise(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::PaymentFailed)->toMail($michael);

        $this->assertStringContainsString('comes as a surprise', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email_on_the_high_queue(): void
    {
        $michael = User::factory()->create();
        $notification = new AccessEnded(reason: AccessEndReasonEnum::Cancelled);

        $this->assertEquals(['mail'], $notification->via($michael));
        $this->assertEquals('high', $notification->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_access_that_ended(): void
    {
        $this->assertEquals(EmailType::AccessEnded, new AccessEnded(reason: AccessEndReasonEnum::Cancelled)->emailType());
    }
}
