<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Enums\PossessionTransactionTypeEnum;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'type',
                'changes_ownership',
                'is_acquisition',
                'occurred_on',
                'counterparty',
                'amount',
                'tax',
                'fees',
                'shipping',
                'entered_total',
                'total',
                'total_source',
                'currency',
                'reference',
                'source_url',
                'note',
                'documents',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_records_a_transaction(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/transactions", [
            'type' => 'purchase',
            'occurred_on' => '2005-03-14',
            'counterparty' => 'Dundies Inc.',
            'amount' => 40,
            'tax' => 3.2,
            'shipping' => 6,
            'currency' => 'usd',
        ])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.total', '49.20')
            ->assertJsonPath('data.attributes.total_source', 'sum')
            ->assertJsonPath('data.attributes.is_acquisition', true);
    }

    #[Test]
    public function it_refuses_a_figure_without_its_currency(): void
    {
        [$user, $vault, $dundie] = $this->scranton();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/transactions", [
            'type' => 'fee',
            'fees' => 85,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    #[Test]
    public function it_lists_the_transactions_of_the_possession_oldest_first_and_nothing_else(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'type' => PossessionTransactionTypeEnum::Fee, 'occurred_on' => '2020-01-03']);
        PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'occurred_on' => '2005-03-14']);
        PossessionTransaction::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/transactions")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.attributes.type', 'purchase')
            ->assertJsonPath('data.1.attributes.type', 'fee');
    }

    #[Test]
    public function it_shows_one_transaction_only_through_its_own_possession(): void
    {
        [$user, $vault, $dundie] = $this->scranton();
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $dundie->id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/transactions/{$transaction->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/possessions/{$mug->uuid}/transactions/{$transaction->uuid}")
            ->assertNotFound();
    }

    #[Test]
    public function it_changes_only_the_fields_it_is_sent(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $dundie->id, 'counterparty' => 'Dundies Inc.', 'amount' => '40.00', 'shipping' => '60.00', 'reference' => 'Invoice 4021']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/transactions/{$purchase->uuid}", [
            'shipping' => 6,
            'reference' => null,
        ])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.counterparty', 'Dundies Inc.')
            ->assertJsonPath('data.attributes.shipping', '6.00')
            ->assertJsonPath('data.attributes.reference', null)
            ->assertJsonPath('data.attributes.total', '46.00');
    }

    #[Test]
    public function it_deletes_a_transaction(): void
    {
        Queue::fake();

        [$user, $vault, $dundie] = $this->scranton();
        $fee = PossessionTransaction::factory()->create(['possession_id' => $dundie->id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$dundie->uuid}/transactions/{$fee->uuid}")
            ->assertNoContent();

        $this->assertModelMissing($fee);
    }

    /** @return array{0: User, 1: Vault, 2: Possession} */
    private function scranton(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award'])];
    }
}
