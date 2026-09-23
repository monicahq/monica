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

    #[Test]
    public function it_seeds_the_catalogue_and_one_example_account_however_often_it_runs(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(MarketplaceCategory::query()->exists());
        $this->assertDatabaseHas('users', ['email' => ExampleAccountSeeder::EMAIL]);
        $this->assertGreaterThanOrEqual(15, Contact::query()->count());
        $this->assertGreaterThanOrEqual(5, Reminder::query()->count());
        $this->assertEquals(0, Contact::query()->whereNull('sort_name')->count());
        $this->assertEquals(0, Contact::query()->whereNull('search_key')->count());

        $this->seed(DatabaseSeeder::class);

        $this->assertCount(1, User::all());
    }
}
