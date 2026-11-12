<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionCondition;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionConditionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionCondition::class, ['possession_condition' => $possessionCondition->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($possessionCondition);
    }
}
