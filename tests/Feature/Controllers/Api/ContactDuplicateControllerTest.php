<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDuplicateControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            [
                'type',
                'id',
                'attributes' => [
                    'contact_id',
                    'other_contact_id',
                    'contact_name',
                    'other_contact_name',
                    'score',
                    'reasons',
                    'dismissed_at',
                    'computed_at',
                ],
            ],
        ],
    ];

    #[Test]
    public function it_lists_the_pairs_of_a_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/duplicates");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.contact_id', $candidate->contact->uuid);
        $response->assertJsonPath('data.0.attributes.score', 70);
    }

    #[Test]
    public function it_puts_the_strongest_resemblance_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->candidateIn($vault, score: 61);
        $strongest = $this->candidateIn($vault, score: 98);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/duplicates");

        $response->assertJsonPath('data.0.id', (string) $strongest->id);
    }

    #[Test]
    public function it_leaves_out_a_pair_somebody_ruled_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault);
        $candidate->dismissed_at = now();
        $candidate->save();

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/duplicates")->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_dismisses_a_pair(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/duplicates/{$candidate->id}/dismissal");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.contact_id', $candidate->contact->uuid);
        $this->assertNotNull($candidate->fresh()->dismissed_at);
    }

    #[Test]
    public function it_does_not_find_a_pair_of_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($elsewhere);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/duplicates/{$candidate->id}/dismissal")
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create();
        $this->candidateIn($stranger);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$stranger->uuid}/duplicates")->assertNotFound();
    }

    private function candidateIn(Vault $vault, int $score = 70): ContactDuplicateCandidate
    {
        $one = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $two = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jimmy', 'last_name' => 'Halpert']);

        return ContactDuplicateCandidate::factory()->create([
            'contact_id' => min($one->id, $two->id),
            'other_contact_id' => max($one->id, $two->id),
            'score' => $score,
        ]);
    }
}
