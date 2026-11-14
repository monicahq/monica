<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AdmitPreviewListEntry;
use App\Enums\AdministratorActionEnum;
use App\Exceptions\AddressAlreadyHoldsAnAccount;
use App\Jobs\LogAdministratorAction;
use App\Models\PreviewListEntry;
use App\Models\User;
use App\Notifications\PreviewListEntryAdmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdmitPreviewListEntryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_emails_a_link_that_opens_sign_up_for_fourteen_days(): void
    {
        Queue::fake();
        Notification::fake();

        $michael = User::factory()->create();
        $entry = PreviewListEntry::factory()->create(['email' => 'holly.flax@dundermifflin.com']);

        $result = new AdmitPreviewListEntry(administrator: $michael, entry: $entry)->execute();

        $this->assertInstanceOf(PreviewListEntry::class, $result);

        $entry->refresh();
        $this->assertNotNull($entry->admitted_at);
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $entry->token_expires_at->timestamp, 5);

        $token = null;
        Notification::assertSentTo($entry, PreviewListEntryAdmitted::class, function (PreviewListEntryAdmitted $notification) use (&$token): bool {
            $token = $notification->token;

            return $notification->queue === 'high';
        });
        $this->assertTrue($entry->is(PreviewListEntry::findByAdmissionToken($token)));

        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::PreviewListEntryAdmitted
            && $job->administrator->is($michael)
            && $job->parameters === ['email' => 'holly.flax@dundermifflin.com']
            && $job->isAbout($entry));
    }

    #[Test]
    public function it_stops_the_previous_link_when_admitting_again(): void
    {
        Queue::fake();
        Notification::fake();

        $entry = PreviewListEntry::factory()->admitted('the-first-token')->create();

        new AdmitPreviewListEntry(administrator: User::factory()->create(), entry: $entry)->execute();

        $this->assertNull(PreviewListEntry::findByAdmissionToken('the-first-token'));
        Notification::assertSentTo($entry, PreviewListEntryAdmitted::class);
    }

    #[Test]
    public function it_refuses_an_entry_that_is_already_registered(): void
    {
        Notification::fake();

        $entry = PreviewListEntry::factory()->registered()->create();

        $this->expectException(AddressAlreadyHoldsAnAccount::class);

        try {
            new AdmitPreviewListEntry(administrator: User::factory()->create(), entry: $entry)->execute();
        } finally {
            Notification::assertNothingSent();
        }
    }

    #[Test]
    public function it_refuses_an_address_that_holds_an_account(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'holly.flax@dundermifflin.com']);
        $entry = PreviewListEntry::factory()->create(['email' => 'holly.flax@dundermifflin.com']);

        $this->expectException(AddressAlreadyHoldsAnAccount::class);
        $this->expectExceptionMessage('holly.flax@dundermifflin.com already holds an account on this instance.');

        new AdmitPreviewListEntry(administrator: User::factory()->create(), entry: $entry)->execute();
    }
}
