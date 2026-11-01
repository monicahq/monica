<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\NotificationTypeEnum;
use App\Jobs\SendNotificationDigests;
use App\Models\Account;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNotificationDigestsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_summary_once_the_hour_the_account_chose_has_come_round(): void
    {
        NotificationFacade::fake();
        CarbonImmutable::setTestNow('2027-05-12 17:00:00');
        $this->accountWaiting();

        new SendNotificationDigests()->handle();

        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_queues_no_second_run_while_the_first_is_waiting_or_running(): void
    {
        Queue::fake();

        SendNotificationDigests::dispatch();
        SendNotificationDigests::dispatch();

        Queue::assertPushedTimes(SendNotificationDigests::class, 1);
    }

    #[Test]
    public function it_waits_until_that_hour(): void
    {
        NotificationFacade::fake();
        CarbonImmutable::setTestNow('2027-05-12 10:00:00');
        $this->accountWaiting();

        new SendNotificationDigests()->handle();

        $this->assertSame(0, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_sends_one_summary_a_day_and_not_one_an_hour(): void
    {
        NotificationFacade::fake();
        CarbonImmutable::setTestNow('2027-05-12 17:00:00');
        $this->accountWaiting();

        new SendNotificationDigests()->handle();
        CarbonImmutable::setTestNow('2027-05-12 19:00:00');
        new SendNotificationDigests()->handle();

        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_spares_a_day_the_account_left_out(): void
    {
        NotificationFacade::fake();
        CarbonImmutable::setTestNow('2027-05-15 17:00:00');
        $account = $this->accountWaiting();
        NotificationPreference::query()
            ->where('account_id', $account->id)
            ->update(['digest_weekdays' => json_encode([1, 2, 3, 4, 5])]);

        new SendNotificationDigests()->handle();

        $this->assertSame(0, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_leaves_an_account_that_wants_everything_at_once_alone(): void
    {
        NotificationFacade::fake();
        CarbonImmutable::setTestNow('2027-05-12 17:00:00');
        $account = $this->accountWaiting();
        NotificationPreference::query()
            ->where('account_id', $account->id)
            ->update(['delivery_mode' => 'immediate']);

        new SendNotificationDigests()->handle();

        $this->assertSame(0, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_sends_a_summary_chosen_after_the_last_run_of_the_day_at_the_first_run_of_the_next(): void
    {
        NotificationFacade::fake();
        $this->travelTo(CarbonImmutable::parse('2027-05-12 22:00:00'));
        $this->accountWaiting(digestTime: '23:30');

        new SendNotificationDigests()->handle();

        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_counts_the_weekday_of_the_chosen_time_rather_than_the_day_it_is_sent(): void
    {
        NotificationFacade::fake();
        $this->travelTo(CarbonImmutable::parse('2027-05-14 22:00:00'));
        $account = $this->accountWaiting(digestTime: '23:30');
        NotificationPreference::query()
            ->where('account_id', $account->id)
            ->update(['digest_weekdays' => json_encode([5])]);

        new SendNotificationDigests()->handle();

        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    private function accountWaiting(string $digestTime = '18:00'): Account
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        NotificationPreference::factory()->digestAt($digestTime)->create([
            'account_id' => $account->id,
            'timezone' => 'Europe/Paris',
        ]);
        Notification::factory()->create(['account_id' => $account->id, 'user_id' => $user->id]);

        return $account;
    }
}
