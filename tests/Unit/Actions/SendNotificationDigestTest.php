<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendNotificationDigest;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Account;
use App\Models\Notification;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\NotificationDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Notifications\RefusingChannel;
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

        $folded = new SendNotificationDigest(account: $account, since: now())->execute();

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

        $this->assertSame(5, new SendNotificationDigest(account: $account, since: now())->execute());

        $summaries = Notification::query()->where('type', NotificationTypeEnum::Digest->value)->get();

        $this->assertCount(2, $summaries);
        $this->assertEqualsCanonicalizing(
            [NotificationChannelEnum::InApp, NotificationChannelEnum::Email],
            $summaries->pluck('channel')->all(),
        );
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

        $this->assertSame(0, new SendNotificationDigest(account: $account, since: now())->execute());

        NotificationFacade::assertNothingSent();
    }

    #[Test]
    public function it_sends_nothing_when_there_is_nothing_to_say(): void
    {
        NotificationFacade::fake();
        [$account] = $this->account();

        $this->assertSame(0, new SendNotificationDigest(account: $account, since: now())->execute());

        NotificationFacade::assertNothingSent();
        $this->assertDatabaseCount('notifications', 0);
    }

    #[Test]
    public function it_never_folds_a_summary_into_another_summary(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        new SendNotificationDigest(account: $account, since: now())->execute();

        $this->assertSame(0, new SendNotificationDigest(account: $account, since: now())->execute());
        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function a_channel_that_refuses_its_summary_does_not_stop_the_next_channel(): void
    {
        $this->app->make(ChannelManager::class)->extend('mail', fn (): RefusingChannel => new RefusingChannel);
        [$account, $user] = $this->account();
        Notification::factory()->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        Notification::factory()->count(2)->create(['account_id' => $account->id, 'user_id' => $user->id]);

        $this->assertSame(2, new SendNotificationDigest(account: $account, since: now())->execute());

        $this->assertSame(1, Notification::query()->where('type', NotificationTypeEnum::Digest->value)->count());
    }

    #[Test]
    public function it_sends_no_second_summary_on_a_channel_already_sent_one_since_the_chosen_time(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        Notification::factory()->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);
        new SendNotificationDigest(account: $account, since: now())->execute();
        Notification::factory()->email()->create(['account_id' => $account->id, 'user_id' => $user->id]);

        $this->assertSame(0, new SendNotificationDigest(account: $account, since: now()->subHour())->execute());

        NotificationFacade::assertSentTimes(NotificationDigest::class, 1);
    }

    #[Test]
    public function it_abandons_what_waits_on_a_channel_the_account_is_no_longer_offered(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Sms->value)
            ->update(['is_enabled' => true, 'destination' => '+15555550100', 'proven_at' => now()]);
        $waiting = Notification::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::Sms,
        ]);
        $account->features()->deactivate('sms-notifications');
        Feature::flushCache();

        $this->assertSame(0, new SendNotificationDigest(account: $account, since: now())->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($waiting->refresh()->failed_at);
    }

    #[Test]
    public function it_abandons_what_waits_on_a_channel_switched_on_but_not_set_up(): void
    {
        NotificationFacade::fake();
        [$account, $user] = $this->account();
        $row = NotificationChannel::query()
            ->where('account_id', $account->id)
            ->where('channel', NotificationChannelEnum::Telegram->value)
            ->firstOrFail();
        $row->update(['is_enabled' => true, 'destination' => null, 'proven_at' => null]);
        $waiting = Notification::factory()->create([
            'account_id' => $account->id,
            'user_id' => $user->id,
            'channel' => NotificationChannelEnum::Telegram,
        ]);

        $this->assertSame(0, new SendNotificationDigest(account: $account, since: now())->execute());

        NotificationFacade::assertNothingSent();
        $this->assertNotNull($waiting->refresh()->failed_at);
        $this->assertSame(1, $row->refresh()->consecutive_failures);
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
