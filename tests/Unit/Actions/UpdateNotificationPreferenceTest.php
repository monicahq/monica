<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateNotificationPreference;
use App\Enums\NotificationDeliveryModeEnum;
use App\Exceptions\NotificationPreferenceIsNotValid;
use App\Models\Account;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateNotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_quiet_hours(): void
    {
        [$user, $preference] = $this->preference();

        new UpdateNotificationPreference(
            user: $user,
            preference: $preference,
            quietHoursAreEnabled: true,
            quietHoursStart: '22:00',
            quietHoursEnd: '08:00',
        )->execute();

        $this->assertDatabaseHas('notification_preferences', [
            'id' => $preference->id,
            'quiet_hours_are_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
        ]);
    }

    #[Test]
    public function it_folds_everything_into_a_summary_at_the_hour_given(): void
    {
        [$user, $preference] = $this->preference();

        new UpdateNotificationPreference(
            user: $user,
            preference: $preference,
            quietHoursAreEnabled: false,
            deliveryMode: NotificationDeliveryModeEnum::Digest,
            digestTime: '18:00',
            digestWeekdays: [1, 2, 3, 4, 5],
        )->execute();

        $this->assertTrue($preference->refresh()->foldsIntoADigest());
        $this->assertEquals([1, 2, 3, 4, 5], $preference->digest_weekdays);
    }

    #[Test]
    public function it_refuses_quiet_hours_with_only_one_end(): void
    {
        [$user, $preference] = $this->preference();

        $this->expectException(NotificationPreferenceIsNotValid::class);

        new UpdateNotificationPreference(
            user: $user,
            preference: $preference,
            quietHoursAreEnabled: true,
            quietHoursStart: '22:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_summary_with_no_hour_to_be_sent_at(): void
    {
        [$user, $preference] = $this->preference();

        $this->expectException(NotificationPreferenceIsNotValid::class);

        new UpdateNotificationPreference(
            user: $user,
            preference: $preference,
            quietHoursAreEnabled: false,
            deliveryMode: NotificationDeliveryModeEnum::Digest,
        )->execute();
    }

    #[Test]
    public function it_refuses_something_that_is_not_a_time_of_day(): void
    {
        [$user, $preference] = $this->preference();

        $this->expectException(NotificationPreferenceIsNotValid::class);

        new UpdateNotificationPreference(
            user: $user,
            preference: $preference,
            quietHoursAreEnabled: true,
            quietHoursStart: '25:00',
            quietHoursEnd: '08:00',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_zone_the_product_does_not_know(): void
    {
        [$user, $preference] = $this->preference();

        $this->expectException(NotificationPreferenceIsNotValid::class);

        new UpdateNotificationPreference(
            user: $user,
            preference: $preference,
            quietHoursAreEnabled: false,
            timezone: 'Middle/Earth',
        )->execute();
    }

    #[Test]
    public function it_does_not_find_preferences_belonging_to_another_account(): void
    {
        [$user] = $this->preference();
        [, $elsewhere] = $this->preference();

        $this->expectException(ModelNotFoundException::class);

        new UpdateNotificationPreference(
            user: $user,
            preference: $elsewhere,
            quietHoursAreEnabled: false,
        )->execute();
    }

    /** @return array{0: User, 1: NotificationPreference} */
    private function preference(): array
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        $preference = NotificationPreference::factory()->create(['account_id' => $account->id]);

        return [$user, $preference];
    }
}
