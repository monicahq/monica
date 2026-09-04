<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Model events are deliberately left on. Everything the example account writes
 * goes through the product's own actions, and a contact takes the name it is
 * sorted and searched by from a saving event, so a seeder that switched events
 * off could not write one at all.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MarketplaceCategorySeeder::class);
        $this->call(ExampleAccountSeeder::class);
    }
}
