<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreatePossessionLoan;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionLoanTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_loan_to_a_contact_of_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        MonicaServer::actingAs($user)->tool(CreatePossessionLoan::class, [
            'vault' => $vault->slug,
            'possession' => $mug->uuid,
            'direction' => 'lent',
            'status' => 'active',
            'contact' => $dwight->uuid,
            'started_on' => '2024-06-12',
        ])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('possession_loans', [
            'possession_id' => $mug->id,
            'contact_id' => $dwight->id,
            'status' => 'active',
        ]);
    }
}
