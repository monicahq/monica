<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Notification;
use App\Models\ReminderOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_prunes_what_is_older_than_the_retention_period(): void
    {
        $old = Notification::factory()->create(['created_at' => now()->subMonths(13)]);
        $recent = Notification::factory()->create(['created_at' => now()->subMonths(11)]);

        $prunable = new Notification()->prunable()->pluck('id');

        $this->assertTrue($prunable->contains($old->id));
        $this->assertFalse($prunable->contains($recent->id));
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
