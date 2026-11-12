<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_a_possession_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        MonicaServer::actingAs($user)->tool(CreatePossessionType::class, [
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Green->value,
            'icon' => 'briefcase',
        ])->assertOk()->assertSee('Office equipment');

        $this->assertDatabaseHas('possession_types', [
            'account_id' => $user->account_id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Green->value,
            'icon' => 'briefcase',
        ]);
    }

    #[Test]
    public function it_refuses_a_possession_type_without_a_name(): void
    {
        MonicaServer::actingAs(User::factory()->create())->tool(CreatePossessionType::class, [
            'name' => '',
        ])->assertHasErrors();

        $this->assertDatabaseCount('possession_types', 0);
    }
}
