<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendNotificationDigest;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\ReminderAnswerEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\NotificationDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNotificationDigestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_folds_everything_waiting_into_one_message(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->count(3)->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);

        $folded = new SendNotificationDigest(account: $account)->execute();

        $this->assertSame(3, $folded);
        NotificationFacade::assertSentTimes(NotificationDigest::class, 1);
        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_writes_one_summary_per_channel_rather_than_one_carrying_them_all(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->count(2)->create(['account_id' => $account->id, 'user_id' => $user->id]);
        Notification::factory()->count(3)->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);

        $this->assertSame(5, new SendNotificationDigest(account: $account)->execute());

        $summaries = Notification::query()->where('type', NotificationTypeEnum::Digest->value)->get();

        $this->assertCount(2, $summaries);
        $this->assertEqualsCanonicalizing(
            [NotificationChannelEnum::InApp, NotificationChannelEnum::Email],
            $summaries->pluck('channel')->all(),
        );
    }

    #[Test]
    public function a_summary_in_the_application_is_its_own_delivery(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->count(2)->create(['account_id' => $account->id, 'user_id' => $user->id]);

        $this->assertSame(2, new SendNotificationDigest(account: $account)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_folds_nothing_for_a_channel_the_account_has_switched_off(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->count(2)->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Email->value)
            ->update(['is_enabled' => false]);

        $this->assertSame(0, new SendNotificationDigest(account: $account)->execute());

        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function the_folded_notifications_keep_their_rows_and_point_at_the_summary(): void
    {
        NotificationFacade::fake();
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
        NotificationFacade::fake();
        [$account] = $this->account();

        $this->assertSame(0, new SendNotificationDigest(account: $account)->execute());

        NotificationFacade::assertNothingSent();
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_leaves_a_notification_quiet_hours_are_still_holding_alone(): void
    {
        NotificationFacade::fake();
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
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        new SendNotificationDigest(account: $account)->execute();

        $this->assertSame(0, new SendNotificationDigest(account: $account)->execute());
        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_leaves_out_a_deadline_answered_while_its_notification_waited(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->count(2)->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        $answered = Notification::factory()->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        $answered->notifiable->update(['answer' => ReminderAnswerEnum::Remembered, 'answered_at' => now()]);

        $folded = new SendNotificationDigest(account: $account)->execute();

        $this->assertSame(2, $folded);
        $this->assertNull($answered->refresh()->digest_id);
    }

    #[Test]
    public function it_marks_the_deadlines_it_carried_as_told(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        $waiting = Notification::factory()->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);

        new SendNotificationDigest(account: $account)->execute();

        $this->assertNotNull($waiting->notifiable->refresh()->notified_at);
    }

    /** @return array{0: Account, 1: User} */
    private function account(): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        NotificationPreference::factory()->digestAt()->create(['account_id' => $account->id]);

        return [$account->refresh(), $user];
    }
}
