<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EmailDeliveryStatusEnum;
use App\Models\Account;
use App\Models\EmailSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailSentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $account = Account::factory()->create();
        $emailSent = EmailSent::factory()->create(['account_id' => $account->id]);

        $this->assertTrue($emailSent->account()->exists());
        $this->assertTrue($account->is($emailSent->account));
    }

    #[Test]
    public function it_belongs_to_a_user(): void
    {
        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);
        $emailSent = EmailSent::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($emailSent->user()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $emailSent->user->email);
    }

    #[Test]
    public function it_has_no_user_when_the_system_emailed_someone_without_an_account(): void
    {
        $emailSent = EmailSent::factory()->withoutUser()->create();

        $this->assertFalse($emailSent->user()->exists());
        $this->assertNull($emailSent->user);
    }

    #[Test]
    public function it_reads_as_sent_while_nothing_has_come_back(): void
    {
        $emailSent = EmailSent::factory()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Sent, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_delivered_once_it_arrived(): void
    {
        $emailSent = EmailSent::factory()->delivered()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Delivered, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_bounced_once_it_came_back(): void
    {
        $emailSent = EmailSent::factory()->bounced()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Bounced, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_bounced_when_the_provider_reported_both(): void
    {
        $emailSent = EmailSent::factory()->delivered()->bounced()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Bounced, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_complained_once_the_recipient_marked_it_as_spam(): void
    {
        $emailSent = EmailSent::factory()->complained()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Complained, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_complained_even_when_it_also_bounced(): void
    {
        $emailSent = EmailSent::factory()->bounced()->complained()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Complained, $emailSent->delivery_status);
    }

    #[Test]
    public function it_reads_as_complained_even_when_it_was_delivered_first(): void
    {
        $emailSent = EmailSent::factory()->delivered()->complained()->create();

        $this->assertSame(EmailDeliveryStatusEnum::Complained, $emailSent->delivery_status);
    }
}
