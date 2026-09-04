<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendNotificationDigest;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendEmail;
use App\Models\Account;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNotificationDigestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_folds_everything_waiting_into_one_message(): void
    {
        Queue::fake();
        [$account, $user] = $this->account();
        Notification::factory()->count(3)->create(['account_id' => $account->id, 'user_id' => $user->id]);

        $folded = new SendNotificationDigest(account: $account)->execute();

        $this->assertSame(3, $folded);
        Queue::assertPushedOn('high', SendEmail::class);
        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function the_folded_notifications_keep_their_rows_and_point_at_the_summary(): void
    {
        Queue::fake();
        [$account, $user] = $this->account();
        $notifications = Notification::factory()->count(2)->create(['account_id' => $account->id, 'user_id' => $user->id]);

        new SendNotificationDigest(account: $account)->execute();

        $digest = Notification::query()->where('type', NotificationTypeEnum::Digest->value)->firstOrFail();

        foreach ($notifications as $notification) {
            $this->assertSame($digest->id, $notification->refresh()->digest_id);
            $this->assertNotNull($notification->sent_at);
        }

        $this->assertSame(2, $digest->folded()->count());
    }

    #[Test]
    public function it_sends_nothing_when_there_is_nothing_to_say(): void
    {
        Queue::fake();
        [$account] = $this->account();

        $this->assertSame(0, new SendNotificationDigest(account: $account)->execute());

        Queue::assertNotPushed(SendEmail::class);
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_leaves_a_notification_quiet_hours_are_still_holding_alone(): void
    {
        Queue::fake();
        [$account, $user] = $this->account();
        Notification::factory()->heldUntil(now()->addHours(6)->toDateTimeString())->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, new SendNotificationDigest(account: $account)->execute());
    }

    #[Test]
    public function it_never_folds_a_summary_into_another_summary(): void
    {
        Queue::fake();
        [$account, $user] = $this->account();
        Notification::factory()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        new SendNotificationDigest(account: $account)->execute();

        $this->assertSame(0, new SendNotificationDigest(account: $account)->execute());
        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    /**
     * @return array{0: Account, 1: User}
     */
    private function account(): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        NotificationPreference::factory()->digestAt()->create(['account_id' => $account->id]);

        return [$account->refresh(), $user];
    }
}
