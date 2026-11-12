<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Models\Account;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillPossessionTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_an_account_made_before_the_feature_the_twenty_five_types(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->migration()->up();

        $this->assertSame(25, PossessionType::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_leaves_an_account_that_holds_a_type_alone(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Dundies']);

        $this->migration()->up();

        $this->assertSame(1, PossessionType::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_adds_nothing_the_second_time_it_runs(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(25, PossessionType::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_gives_nothing_to_an_account_nobody_signs_in_to(): void
    {
        $account = Account::factory()->create();

        $this->migration()->up();

        $this->assertFalse(PossessionType::query()->where('account_id', $account->id)->exists());
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_26_000001_backfill_possession_types.php');
    }
}
