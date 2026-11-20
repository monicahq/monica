<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTimelineControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            '*' => [
                'type',
                'attributes' => ['section', 'kind', 'date', 'date_precision', 'is_current', 'is_acquisition', 'record_id'],
                'links' => ['record'],
            ],
        ],
    ];

    #[Test]
    public function it_lists_the_timeline_of_a_possession(): void
    {
        [$user, $vault, $costume] = $this->armadillo();
        PossessionMaintenanceRecord::factory()->create(['possession_id' => $costume->id, 'performed_on' => '2026-09-14']);
        PossessionLoan::factory()->returned()->create(['possession_id' => $costume->id, 'started_on' => '2026-10-12', 'returned_on' => '2026-11-02']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/possessions/{$costume->uuid}/timeline")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonCount(3, 'data');

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/possessions/{$costume->uuid}/timeline", ['section' => 'maintenance'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_refuses_a_section_the_timeline_does_not_have(): void
    {
        [$user, $vault, $costume] = $this->armadillo();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/possessions/{$costume->uuid}/timeline", ['section' => 'documents'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('section');
    }

    #[Test]
    public function it_does_not_find_a_possession_through_the_wrong_vault(): void
    {
        [$user, , $costume] = $this->armadillo();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stamford->uuid}/possessions/{$costume->uuid}/timeline")->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function armadillo(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'name' => 'Holiday Armadillo costume'])];
    }
}
