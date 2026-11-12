<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Possessions;

use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTransactionDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'documents' => [
                    '*' => ['id', 'name', 'mime_type', 'size_in_bytes'],
                ],
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_attaches_a_document(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();

        Sanctum::actingAs($user);

        $this->post("/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/transactions/{$purchase->uuid}/documents", [
            'file' => UploadedFile::fake()->create('invoice-4021.pdf', 4, 'application/pdf'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.documents.0.name', 'invoice-4021.pdf');
    }

    #[Test]
    public function it_refuses_an_attachment_without_a_file(): void
    {
        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/transactions/{$purchase->uuid}/documents")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function it_removes_a_document(): void
    {
        Queue::fake();

        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();
        $invoice = File::factory()->used(1)->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $purchase->documents()->attach($invoice);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/transactions/{$purchase->uuid}/documents/{$invoice->uuid}")
            ->assertNoContent();

        $this->assertSame(0, $purchase->documents()->count());
    }

    #[Test]
    public function a_document_of_another_transaction_is_not_found(): void
    {
        [$user, $vault, $sebring, $purchase] = $this->sebringPurchase();
        $shipping = PossessionTransaction::factory()->create(['possession_id' => $sebring->id]);
        $invoice = File::factory()->used(1)->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $shipping->documents()->attach($invoice);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/vaults/{$vault->slug}/possessions/{$sebring->uuid}/transactions/{$purchase->uuid}/documents/{$invoice->uuid}")
            ->assertNotFound();
    }

    #[Test]
    public function a_transaction_of_another_account_is_not_found(): void
    {
        [$user] = $this->sebringPurchase();
        [, $otherVault, $otherSebring, $otherPurchase] = $this->sebringPurchase();

        Sanctum::actingAs($user);

        $this->json('POST', "/api/v1/vaults/{$otherVault->slug}/possessions/{$otherSebring->uuid}/transactions/{$otherPurchase->uuid}/documents")
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Possession, 3: PossessionTransaction} */
    private function sebringPurchase(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);

        return [$user, $vault, $sebring, PossessionTransaction::factory()->create(['possession_id' => $sebring->id])];
    }
}
