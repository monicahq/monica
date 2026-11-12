<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionCondition;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        MonicaServer::actingAs($user)->tool(CreatePossessionCondition::class, ['name' => 'Well loved', 'colour' => VaultColourEnum::Amber->value])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Well loved');

        $this->assertDatabaseHas('possession_conditions', [
            'account_id' => $user->account_id,
            'name' => 'Well loved',
            'colour' => VaultColourEnum::Amber->value,
        ]);
    }

    #[Test]
    public function it_refuses_a_name_the_account_already_uses(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Well loved']);

        MonicaServer::actingAs($user)->tool(CreatePossessionCondition::class, ['name' => 'well loved'])
            ->assertHasErrors();
    }
}
