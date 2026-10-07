<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MarketplaceCategorySeeder::class);

        if (! app()->environment("production")) {
            $this->call(ExampleAccountSeeder::class);
        }
    }
}
