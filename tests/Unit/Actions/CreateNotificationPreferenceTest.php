<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateNotificationPreference;
use App\Enums\NotificationDeliveryModeEnum;
use App\Models\Account;
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
            'in_app_is_enabled' => true,
            'email_is_enabled' => true,
            'push_is_enabled' => false,
            'quiet_hours_are_enabled' => false,
            'delivery_mode' => NotificationDeliveryModeEnum::Immediate->value,
            'timezone' => 'America/New_York',
        ]);
    }

    #[Test]
    public function it_falls_back_to_utc_when_the_account_holds_nobody(): void
    {
        $account = Account::factory()->create();

        $preference = new CreateNotificationPreference(account: $account)->execute();

        $this->assertSame('UTC', $preference->timezone);
    }
}
