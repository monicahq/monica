<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionLoan;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionLoan::class, [
            'vault' => $vault->slug,
            'possession' => $mug->uuid,
            'loan' => $loan->uuid,
        ])
            ->assertOk()
            ->assertSee('The loan is deleted.');

        $this->assertModelMissing($loan);
    }
}
