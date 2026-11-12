<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdatePossessionLoan;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_a_loan_as_returned_and_keeps_what_it_is_not_sent(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'purpose' => 'Beet harvest party']);

        MonicaServer::actingAs($user)->tool(UpdatePossessionLoan::class, [
            'vault' => $vault->slug,
            'possession' => $mug->uuid,
            'loan' => $loan->uuid,
            'status' => 'returned',
            'returned_on' => '2024-06-14',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $loan->refresh();
        $this->assertSame('returned', $loan->status->value);
        $this->assertSame('2024-06-14', $loan->returned_on?->toDateString());
        $this->assertSame('Beet harvest party', $loan->purpose);
    }
}
