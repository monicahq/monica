<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionLocationStay;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionLocationStayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_sent(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $stay = PossessionLocationStay::factory()->create(['possession_id' => $press->id, 'left_on' => '2024-06-04', 'reason' => 'After the harvest']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionLocationStay::class, [
            'vault' => $vault->slug,
            'possession' => $press->uuid,
            'stay' => $stay->uuid,
            'note' => 'Mose remembers it well.',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $stay->refresh();
        $this->assertSame('Mose remembers it well.', $stay->note);
        $this->assertSame('After the harvest', $stay->reason);
        $this->assertSame('2024-06-04', $stay->left_on?->toDateString());
    }
}
