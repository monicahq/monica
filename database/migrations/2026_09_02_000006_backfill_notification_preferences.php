<?php

declare(strict_types=1);

use App\Enums\NotificationChannelEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('accounts')
            ->leftJoin('notification_preferences', 'notification_preferences.account_id', '=', 'accounts.id')
            ->whereNull('notification_preferences.id')
            ->select('accounts.id')
            ->orderBy('accounts.id')
            ->chunk(500, function ($accounts) use ($now): void {
                $rows = [];

                foreach ($accounts as $account) {
                    $rows[] = [
                        'account_id' => $account->id,
                        'quiet_hours_are_enabled' => false,
                        'quiet_hours_start' => null,
                        'quiet_hours_end' => null,
                        'delivery_mode' => 'immediate',
                        'digest_time' => null,
                        'digest_weekdays' => null,
                        'timezone' => $this->zoneOf($account->id),
                        'type_overrides' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('notification_preferences')->insert($rows);
                }
            });

        DB::table('accounts')
            ->leftJoin('notification_channels', 'notification_channels.account_id', '=', 'accounts.id')
            ->whereNull('notification_channels.id')
            ->select('accounts.id')
            ->orderBy('accounts.id')
            ->chunk(500, function ($accounts) use ($now): void {
                $rows = [];

                foreach ($accounts as $account) {
                    foreach (NotificationChannelEnum::cases() as $channel) {
                        $rows[] = [
                            'account_id' => $account->id,
                            'channel' => $channel->value,
                            'is_enabled' => $this->onByDefault($channel),
                            'destination' => null,
                            'verification_token_hash' => null,
                            'proven_at' => null,
                            'proof_code_hash' => null,
                            'proof_expires_at' => null,
                            'proof_attempts' => 0,
                            'proof_sent_count' => 0,
                            'proof_last_sent_at' => null,
                            'consecutive_failures' => 0,
                            'disabled_reason' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('notification_channels')->insert($rows);
                }
            });
    }

    private function onByDefault(NotificationChannelEnum $channel): bool
    {
        return ! $channel->needsDestination();
    }

    private function zoneOf(int $accountId): string
    {
        $zone = DB::table('users')
            ->where('account_id', $accountId)
            ->orderBy('id')
            ->value('timezone');

        return is_string($zone) && $zone !== '' ? $zone : 'UTC';
    }
};
