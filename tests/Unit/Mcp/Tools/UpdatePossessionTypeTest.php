<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\VaultColourEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionType;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Vehicle',
            'colour' => VaultColourEnum::Rust->value,
            'icon' => 'car',
        ]);

        MonicaServer::actingAs($user)->tool(UpdatePossessionType::class, [
            'possession_type' => $type->uuid,
            'name' => 'Company car',
        ])->assertOk()->assertHasNoErrors()->assertSee('Company car');

        $this->assertDatabaseHas('possession_types', [
            'id' => $type->id,
            'name' => 'Company car',
            'colour' => VaultColourEnum::Rust->value,
            'icon' => 'car',
        ]);
    }

    #[Test]
    public function it_removes_the_icon_when_given_an_empty_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'icon' => 'car']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionType::class, [
            'possession_type' => $type->uuid,
            'icon' => '',
        ])->assertOk()->assertHasNoErrors();

        $this->assertNull($type->refresh()->icon);
    }

    #[Test]
    public function it_changes_the_colour_and_keeps_the_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionType::class, [
            'possession_type' => $type->uuid,
            'colour' => VaultColourEnum::Plum->value,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('possession_types', [
            'id' => $type->id,
            'name' => 'Vehicle',
            'colour' => VaultColourEnum::Plum->value,
        ]);
    }

    #[Test]
    public function it_reports_a_possession_type_of_another_account_as_not_existing(): void
    {
        $type = PossessionType::factory()->create(['name' => 'Vehicle']);

        MonicaServer::actingAs(User::factory()->create())->tool(UpdatePossessionType::class, [
            'possession_type' => $type->uuid,
            'name' => 'Company car',
        ])->assertHasErrors();

        $this->assertSame('Vehicle', $type->refresh()->name);
    }
}
