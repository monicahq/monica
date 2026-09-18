<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateNotificationPreference;
use App\Enums\NotificationChannelEnum;
use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Account;
use App\Models\NotificationChannel;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_an_account_the_row_delivery_reads(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id, 'timezone' => 'America/New_York']);

        $preference = new CreateNotificationPreference(account: $account->refresh())->execute();

        $this->assertInstanceOf(NotificationPreference::class, $preference);
        $this->assertDatabaseHas('notification_preferences', [
            'account_id' => $account->id,
            'quiet_hours_are_enabled' => false,
            'delivery_mode' => NotificationDeliveryModeEnum::Immediate->value,
            'timezone' => 'America/New_York',
        ]);
    }

    #[Test]
    public function it_gives_the_account_one_row_per_channel(): void
    {
        $account = Account::factory()->create();

        new CreateNotificationPreference(account: $account)->execute();

        foreach (NotificationChannelEnum::cases() as $channel) {
            $this->assertDatabaseHas('notification_channels', [
                'account_id' => $account->id,
                'channel' => $channel->value,
            ]);
        }

        $this->assertSame(4, NotificationChannel::query()->where('account_id', $account->id)->count());
    }

    #[Test]
    public function only_the_channels_asking_for_nothing_are_switched_on_to_start_with(): void
    {
        $account = Account::factory()->create();

        new CreateNotificationPreference(account: $account)->execute();

        $this->assertDatabaseHas('notification_channels', ['account_id' => $account->id, 'channel' => 'in_app', 'is_enabled' => true]);
        $this->assertDatabaseHas('notification_channels', ['account_id' => $account->id, 'channel' => 'email', 'is_enabled' => true]);

        foreach (['telegram', 'sms'] as $channel) {
            $this->assertDatabaseHas('notification_channels', ['account_id' => $account->id, 'channel' => $channel, 'is_enabled' => false]);
        }
    }

    #[Test]
    public function it_falls_back_to_utc_when_the_account_holds_nobody(): void
    {
        $account = Account::factory()->create();

        $preference = new CreateNotificationPreference(account: $account)->execute();

        $this->assertSame('UTC', $preference->timezone);
    }
    #[Test]
    public function it_writes_nothing_when_the_account_already_has_the_row(): void
    {
        $account = Account::factory()->create();

        new CreateNotificationPreference(account: $account)->execute();
        new CreateNotificationPreference(account: $account)->execute();

        $this->assertSame(1, NotificationPreference::query()->where('account_id', $account->id)->count());
        $this->assertSame(4, NotificationChannel::query()->where('account_id', $account->id)->count());
    }
}
