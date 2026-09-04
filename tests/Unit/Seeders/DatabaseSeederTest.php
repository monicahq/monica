<?php

declare(strict_types=1);

namespace Tests\Unit\Seeders;

use App\Models\Contact;
use App\Models\MarketplaceCategory;
use App\Models\Reminder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ExampleAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Run the whole thing the way `db:seed` does, rather than each seeder on its
     * own. A contact takes the name it is sorted by from a saving event, so a
     * seeder that switches model events off writes the categories and then
     * fails on the first person, which is a failure only this test sees.
     */
    #[Test]
    public function it_seeds_the_catalogue_and_the_example_account_together(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(MarketplaceCategory::query()->exists());
        $this->assertDatabaseHas('users', ['email' => ExampleAccountSeeder::EMAIL]);
        $this->assertGreaterThanOrEqual(15, Contact::query()->count());
        $this->assertGreaterThanOrEqual(5, Reminder::query()->count());
    }

    #[Test]
    public function it_gives_every_contact_the_name_it_is_sorted_and_searched_by(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(0, Contact::query()->whereNull('sort_name')->count());
        $this->assertEquals(0, Contact::query()->whereNull('search_key')->count());
    }

    #[Test]
    public function it_can_be_run_again_without_building_a_second_account(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertCount(1, User::all());
    }
}
