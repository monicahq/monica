<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\Account;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_an_account_made_before_the_feature_the_row_delivery_reads(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id, 'timezone' => 'America/New_York']);
        NotificationPreference::query()->where('account_id', $account->id)->delete();

        $this->migration()->up();

        $this->assertDatabaseHas('notification_preferences', [
            'account_id' => $account->id,
            'quiet_hours_are_enabled' => false,
            'delivery_mode' => 'immediate',
            'timezone' => 'America/New_York',
        ]);
    }

    #[Test]
    public function it_gives_an_account_made_before_the_feature_one_row_per_channel(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);
        NotificationPreference::query()->where('account_id', $account->id)->delete();
        DB::table('notification_channels')->where('account_id', $account->id)->delete();

        $this->migration()->up();

        $this->assertSame(4, DB::table('notification_channels')->where('account_id', $account->id)->count());
        $this->assertDatabaseHas('notification_channels', ['account_id' => $account->id, 'channel' => 'email', 'is_enabled' => true]);
        $this->assertDatabaseHas('notification_channels', ['account_id' => $account->id, 'channel' => 'telegram', 'is_enabled' => false]);
    }

    #[Test]
    public function it_adds_nothing_the_second_time_it_runs(): void
    {
        $account = Account::factory()->create();
        User::factory()->create(['account_id' => $account->id]);
        NotificationPreference::query()->where('account_id', $account->id)->delete();

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(1, NotificationPreference::query()->where('account_id', $account->id)->count());
    }

    #[Test]
    public function it_leaves_an_account_that_already_has_one_alone(): void
    {
        $preference = NotificationPreference::factory()->create(['timezone' => 'Australia/Sydney']);

        $this->migration()->up();

        $this->assertSame(1, NotificationPreference::query()->where('account_id', $preference->account_id)->count());
        $this->assertSame('Australia/Sydney', $preference->refresh()->timezone);
    }

    #[Test]
    public function it_falls_back_to_utc_for_an_account_holding_nobody(): void
    {
        $account = Account::factory()->create();

        $this->migration()->up();

        $this->assertDatabaseHas('notification_preferences', [
            'account_id' => $account->id,
            'timezone' => 'UTC',
        ]);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_09_02_000006_backfill_notification_preferences.php');
    }
}
