<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLoanControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'direction',
                'status',
                'is_overdue',
                'contact_id',
                'contact_name',
                'started_on',
                'due_on',
                'returned_on',
                'condition_out_id',
                'condition_back_id',
                'deposit_amount',
                'deposit_currency',
                'purpose',
                'is_in_provenance',
                'created_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_records_a_loan(): void
    {
        Queue::fake();

        [$user, $vault, $mug] = $this->dunderMifflin();
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans", [
            'direction' => 'lent',
            'status' => 'active',
            'contact_id' => $dwight->uuid,
            'started_on' => '2024-06-12',
            'deposit_amount' => 500,
            'deposit_currency' => 'usd',
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.contact_id', $dwight->uuid)
            ->assertJsonPath('data.attributes.contact_name', 'Dwight Schrute')
            ->assertJsonPath('data.attributes.deposit_amount', '500.00')
            ->assertJsonPath('data.attributes.is_overdue', false);
    }

    #[Test]
    public function it_says_so_when_the_vault_has_no_template_for_new_contacts(): void
    {
        [$user, $vault, $mug] = $this->dunderMifflin();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans", [
            'direction' => 'lent',
            'status' => 'active',
            'new_contact' => ['first_name' => 'Nate'],
            'started_on' => '2024-06-12',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['new_contact' => 'This vault has no template for new contacts to start from. Choose one in the vault settings.']);

        $this->assertDatabaseCount('possession_loans', 0);
    }

    #[Test]
    public function it_refuses_a_second_active_loan(): void
    {
        [$user, $vault, $mug] = $this->dunderMifflin();
        PossessionLoan::factory()->active()->create(['possession_id' => $mug->id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans", [
            'direction' => 'lent',
            'status' => 'active',
            'contact_id' => $dwight->uuid,
            'started_on' => '2024-06-12',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    #[Test]
    public function it_answers_not_found_for_a_contact_of_another_vault(): void
    {
        [$user, $vault, $mug] = $this->dunderMifflin();
        $elsewhere = Contact::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans", [
            'direction' => 'lent',
            'status' => 'active',
            'contact_id' => $elsewhere->uuid,
            'started_on' => '2024-06-12',
        ])->assertNotFound();
    }

    #[Test]
    public function it_lists_the_loans_of_the_possession_only_newest_first(): void
    {
        [$user, $vault, $mug] = $this->dunderMifflin();
        $older = PossessionLoan::factory()->returned()->create(['possession_id' => $mug->id, 'started_on' => '2024-01-02', 'returned_on' => '2024-01-05']);
        $newer = PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'started_on' => '2024-06-12']);
        PossessionLoan::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->uuid)
            ->assertJsonPath('data.1.id', $older->uuid);
    }

    #[Test]
    public function it_shows_a_loan(): void
    {
        [$user, $vault, $mug] = $this->dunderMifflin();
        $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.id', $loan->uuid);
    }

    #[Test]
    public function it_changes_only_the_fields_it_is_sent(): void
    {
        Queue::fake();

        [$user, $vault, $mug] = $this->dunderMifflin();
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'purpose' => 'Beet harvest party', 'due_on' => '2024-06-15']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}", [
            'status' => 'returned',
            'returned_on' => '2024-06-14',
        ])
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'returned')
            ->assertJsonPath('data.attributes.returned_on', '2024-06-14')
            ->assertJsonPath('data.attributes.purpose', 'Beet harvest party')
            ->assertJsonPath('data.attributes.due_on', '2024-06-15')
            ->assertJsonPath('data.attributes.contact_id', $loan->contact->uuid);
    }

    #[Test]
    public function it_deletes_a_loan(): void
    {
        Queue::fake();

        [$user, $vault, $mug] = $this->dunderMifflin();
        $loan = PossessionLoan::factory()->create(['possession_id' => $mug->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($loan);
    }

    #[Test]
    public function it_answers_not_found_for_a_loan_of_another_possession(): void
    {
        [$user, $vault, $mug] = $this->dunderMifflin();
        $loan = PossessionLoan::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/loans/{$loan->uuid}")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function dunderMifflin(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug'])];
    }
}
