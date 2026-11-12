<?php

declare(strict_types=1);

use App\Actions\CreateDefaultLocations;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->whereDoesntHave('account.locations')
            ->chunkById(200, function ($users): void {
                foreach ($users as $user) {
                    new CreateDefaultLocations(user: $user)->execute();
                }
            });
    }
};
