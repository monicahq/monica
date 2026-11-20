<?php

declare(strict_types=1);

use App\Jobs\SupplyAnimalTemplate;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                SupplyAnimalTemplate::dispatch(user: $user)->onQueue('low');
            }
        });
    }
};
