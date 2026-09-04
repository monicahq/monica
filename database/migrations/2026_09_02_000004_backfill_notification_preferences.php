<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every account that already exists the preferences a new one is now
     * created with.
     *
     * Delivery reads this row on every send and is written on the promise that
     * it is there, so an account made before the feature existed cannot be left
     * without one. It is a migration rather than a command because it has to run
     * exactly once, everywhere, without anybody remembering to run it.
     *
     * It reads and writes through the query builder rather than through the
     * models, so that a later change to either leaves this untouched.
     */
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

    /**
     * The zone of the one user the account holds, and UTC for an account that
     * somehow holds none.
     */
    private function zoneOf(int $accountId): string
    {
        $zone = DB::table('users')
            ->where('account_id', $accountId)
            ->orderBy('id')
            ->value('timezone');

        return is_string($zone) && $zone !== '' ? $zone : 'UTC';
    }
};
