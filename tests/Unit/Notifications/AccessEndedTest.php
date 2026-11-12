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
    public function it_gives_every_reason_its_own_wording(): void
    {
        $michael = User::factory()->create();

        $wordings = [];

        foreach (AccessEndReasonEnum::cases() as $reason) {
            $rendered = (string) new AccessEnded(reason: $reason)->toMail($michael)->render();

            $wordings[] = $rendered;
        }

        $this->assertCount(count(AccessEndReasonEnum::cases()), array_unique($wordings));
    }

    #[Test]
    public function it_sends_the_reader_to_the_offers(): void
    {
        $michael = User::factory()->create();

        $message = new AccessEnded(reason: AccessEndReasonEnum::Cancelled)->toMail($michael);

        $this->assertEquals(route('settings.billing.index'), $message->actionUrl);
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
