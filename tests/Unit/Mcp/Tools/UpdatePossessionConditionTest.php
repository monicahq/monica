<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionCondition;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Worn',
            'colour' => VaultColourEnum::Amber->value,
            'icon' => 'heart',
        ]);

        MonicaServer::actingAs($user)->tool(UpdatePossessionCondition::class, [
            'possession_condition' => $possessionCondition->uuid,
            'name' => 'Well loved',
        ])->assertOk()->assertHasNoErrors()->assertSee('Well loved');

        $this->assertDatabaseHas('possession_conditions', [
            'id' => $possessionCondition->id,
            'name' => 'Well loved',
            'colour' => VaultColourEnum::Amber->value,
            'icon' => 'heart',
        ]);
    }

    #[Test]
    public function it_removes_the_icon_when_given_an_empty_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'icon' => 'heart']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionCondition::class, [
            'possession_condition' => $possessionCondition->uuid,
            'icon' => '',
        ])->assertOk()->assertHasNoErrors();

        $this->assertNull($possessionCondition->refresh()->icon);
    }

    #[Test]
    public function it_reports_a_condition_of_another_account_as_not_existing(): void
    {
        $possessionCondition = PossessionCondition::factory()->create(['name' => 'Mint']);

        MonicaServer::actingAs(User::factory()->create())->tool(UpdatePossessionCondition::class, [
            'possession_condition' => $possessionCondition->uuid,
            'name' => 'Pristine',
        ])->assertHasErrors();

        $this->assertSame('Mint', $possessionCondition->refresh()->name);
    }
}
