<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionType;
use App\Models\Possession;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_possession_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionType::class, ['possession_type' => $type->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($type);
    }

    #[Test]
    public function it_says_how_many_possessions_went_with_the_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->count(2)->create(['vault_id' => $vault->id, 'possession_type_id' => $type->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionType::class, ['possession_type' => $type->uuid])
            ->assertOk();
    }

    #[Test]
    public function it_reports_a_possession_type_of_another_account_as_not_existing(): void
    {
        $type = PossessionType::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(DeletePossessionType::class, ['possession_type' => $type->uuid])
            ->assertHasErrors();

        $this->assertModelExists($type);
    }
}
