<?php

declare(strict_types=1);

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
                        'in_app_is_enabled' => true,
                        'email_is_enabled' => true,
                        'push_is_enabled' => false,
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
