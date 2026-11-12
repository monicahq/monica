<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillLocationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_an_account_made_before_the_feature_the_six_locations(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->migration()->up();

        $this->assertSame(6, Location::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_leaves_an_account_that_holds_a_location_alone(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);

        $this->migration()->up();

        $this->assertSame(1, Location::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_adds_nothing_the_second_time_it_runs(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(6, Location::query()->where('account_id', $user->account_id)->count());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_26_000007_backfill_locations.php');
    }
}
