<?php

declare(strict_types=1);

use App\Jobs\SupplyContactTemplates;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                SupplyContactTemplates::dispatch(user: $user)->onQueue('low');
            }
        });
    }
};
