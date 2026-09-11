<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\ReminderOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_and_a_user(): void
    {
        $notification = Notification::factory()->create();

        $this->assertTrue($notification->account()->exists());
        $this->assertTrue($notification->user()->exists());
    }

    #[Test]
    public function it_is_about_a_reminder_deadline(): void
    {
        $notification = Notification::factory()->create();

        $this->assertInstanceOf(ReminderOccurrence::class, $notification->notifiable);
    }

    #[Test]
    public function a_summary_is_about_nothing_in_particular(): void
    {
        $digest = Notification::factory()->digest()->create();

        $this->assertNull($digest->notifiable);
    }

    #[Test]
    public function it_points_at_the_summary_that_carried_it(): void
    {
        $digest = Notification::factory()->digest()->create();
        $folded = Notification::factory()->create([
            'account_id' => $digest->account_id,
            'user_id' => $digest->user_id,
            'digest_id' => $digest->id,
        ]);

        $this->assertTrue($folded->digest()->exists());
        $this->assertTrue($digest->folded->contains($folded->id));
    }

    #[Test]
    public function it_finds_what_may_go_out_now(): void
    {
        $ready = Notification::factory()->create();
        $sent = Notification::factory()->sent()->create();
        $held = Notification::factory()->heldUntil(now()->addHours(4)->toDateTimeString())->create();

        $found = Notification::query()->deliverable()->pluck('id');

        $this->assertTrue($found->contains($ready->id));
        $this->assertFalse($found->contains($sent->id));
        $this->assertFalse($found->contains($held->id));
    }

    #[Test]
    public function it_never_sends_a_notification_already_folded_into_a_summary(): void
    {
        $digest = Notification::factory()->digest()->create();
        $folded = Notification::factory()->create([
            'account_id' => $digest->account_id,
            'user_id' => $digest->user_id,
            'digest_id' => $digest->id,
        ]);

        $this->assertFalse(Notification::query()->deliverable()->pluck('id')->contains($folded->id));
    }

    #[Test]
    public function it_tells_one_person_about_one_thing_once_per_channel(): void
    {
        $this->freezeTime();

        $notification = Notification::factory()->create();

        $written = Notification::query()->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'account_id' => $notification->account_id,
            'user_id' => $notification->user_id,
            'type' => $notification->type->value,
            'channel' => $notification->channel->value,
            'notifiable_type' => $notification->notifiable_type,
            'notifiable_id' => $notification->notifiable_id,
            'title' => 'Again',
            'body' => 'Again',
            'locale' => 'en',
            'scheduled_for' => now(),
            'held_until' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $written);
    }

    #[Test]
    public function it_prunes_what_is_older_than_the_retention_period(): void
    {
        $old = Notification::factory()->create(['created_at' => now()->subMonths(13)]);
        $recent = Notification::factory()->create(['created_at' => now()->subMonths(11)]);

        $prunable = new Notification()->prunable()->pluck('id');

        $this->assertTrue($prunable->contains($old->id));
        $this->assertFalse($prunable->contains($recent->id));
    }

    #[Test]
    public function the_retention_period_is_a_setting_of_the_instance(): void
    {
        config(['monica.notification_retention_months' => 1]);

        $notification = Notification::factory()->create(['created_at' => now()->subMonths(2)]);

        $this->assertTrue(new Notification()->prunable()->pluck('id')->contains($notification->id));
    }

    #[Test]
    public function pruning_leaves_the_deadline_saying_that_somebody_was_told(): void
    {
        $notification = Notification::factory()->create([
            'created_at' => now()->subMonths(13),
            'sent_at' => now()->subMonths(13),
        ]);
        $occurrence = ReminderOccurrence::query()->findOrFail($notification->notifiable_id);
        $occurrence->update(['notified_at' => now()->subMonths(13)]);

        new Notification()->prunable()->delete();

        $this->assertModelMissing($notification);
        $this->assertNotNull($occurrence->refresh()->notified_at);
    }
}
