<?php

declare(strict_types=1);

use App\Actions\RebuildOrderingKeys;
use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Account::query()->chunkById(100, function ($accounts): void {
            foreach ($accounts as $account) {
                DB::transaction(fn () => new RebuildOrderingKeys(account: $account, locale: $account->locale())->execute());
            }
        });
    }
};
