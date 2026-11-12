<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossession;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_what_is_left_out_and_clears_what_is_sent_null(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Chrysler Sebring',
            'identifier' => '1C3EL55R',
            'current_location_id' => Location::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        MonicaServer::actingAs($user)->tool(UpdatePossession::class, [
            'vault' => $vault->uuid,
            'possession' => $sebring->uuid,
            'status' => 'sold',
            'location_id' => null,
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $sebring->refresh();

        $this->assertSame('Chrysler Sebring', $sebring->name);
        $this->assertSame('1C3EL55R', $sebring->identifier);
        $this->assertSame('sold', $sebring->status->value);
        $this->assertNull($sebring->current_location_id);
    }

    #[Test]
    public function it_keeps_its_declared_value_and_its_tags_when_they_are_left_out(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id, 'declared_value' => '12.00', 'declared_value_currency' => 'usd']);
        $mug->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']));

        MonicaServer::actingAs($user)->tool(UpdatePossession::class, [
            'vault' => $vault->uuid,
            'possession' => $mug->uuid,
            'name' => 'World’s Best Boss mug',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $mug->refresh();

        $this->assertSame('12.00', $mug->declared_value);
        $this->assertEquals(['insured'], $mug->tags->pluck('name')->all());
    }
}
