<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\AccountExportStatusEnum;
use App\Jobs\BuildAccountExport;
use App\Models\AccountExport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'status',
            'vaults',
            'size_in_bytes',
            'ready_at',
            'expires_at',
            'created_at',
        ],
        'links' => ['self'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();

        $this->michael = User::factory()->create();
    }

    #[Test]
    public function it_lists_the_exports_of_the_account_newest_first(): void
    {
        $older = AccountExport::factory()->create(['user_id' => $this->michael->id, 'status' => AccountExportStatusEnum::Expired]);
        $newer = AccountExport::factory()->create(['user_id' => $this->michael->id, 'status' => AccountExportStatusEnum::Ready]);
        AccountExport::factory()->create();
        Sanctum::actingAs($this->michael);

        $response = $this->json('GET', '/api/v1/exports');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonStructure],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $newer->uuid);
        $response->assertJsonPath('data.1.id', $older->uuid);
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_shows_an_export(): void
    {
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Scranton']);
        $export = AccountExport::factory()->create([
            'user_id' => $this->michael->id,
            'vault_ids' => [$scranton->id],
            'status' => AccountExportStatusEnum::Ready,
            'size_in_bytes' => 48211,
            'ready_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        Sanctum::actingAs($this->michael);

        $response = $this->json('GET', "/api/v1/exports/{$export->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.type', 'account_export');
        $response->assertJsonPath('data.attributes.status', 'ready');
        $response->assertJsonPath('data.attributes.vaults', [$scranton->uuid]);
        $response->assertJsonPath('data.attributes.size_in_bytes', 48211);
    }

    #[Test]
    public function it_answers_not_found_for_an_export_of_another_account(): void
    {
        $export = AccountExport::factory()->create();
        Sanctum::actingAs($this->michael);

        $this->json('GET', "/api/v1/exports/{$export->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_prepares_an_export_of_every_vault(): void
    {
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Scranton']);
        $stamford = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Stamford']);

        $response = $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->json('POST', '/api/v1/exports');

        $response->assertAccepted();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.status', 'preparing');
        $response->assertJsonPath('data.attributes.vaults', [$scranton->uuid, $stamford->uuid]);
        Queue::assertPushed(BuildAccountExport::class);
    }

    #[Test]
    public function it_prepares_an_export_of_the_vaults_it_is_named(): void
    {
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Scranton']);
        Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Stamford']);
        $nashua = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Nashua']);

        $response = $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->json('POST', '/api/v1/exports', ['vaults' => [$scranton->slug, $nashua->uuid]]);

        $response->assertAccepted();
        $response->assertJsonPath('data.attributes.vaults', [$scranton->uuid, $nashua->uuid]);
    }

    #[Test]
    public function it_refuses_a_key_without_the_sensitive_fields_scope(): void
    {
        Vault::factory()->create(['account_id' => $this->michael->account_id]);

        $this->withToken($this->michael->createToken('Michael’s script')->plainTextToken)
            ->json('POST', '/api/v1/exports')
            ->assertForbidden();

        $this->assertDatabaseCount('account_exports', 0);
    }

    #[Test]
    public function it_refuses_a_second_export_while_one_is_being_prepared(): void
    {
        AccountExport::factory()->create(['user_id' => $this->michael->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->json('POST', '/api/v1/exports')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['export' => 'An export is already being prepared.']);
    }

    #[Test]
    public function it_refuses_an_empty_list_of_vaults(): void
    {
        $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->json('POST', '/api/v1/exports', ['vaults' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vaults');
    }

    #[Test]
    public function it_answers_not_found_for_a_vault_of_another_account(): void
    {
        $stamford = Vault::factory()->create(['name' => 'Stamford']);

        $this->withToken($this->michael->createToken('Michael’s script', ['sensitive-fields'])->plainTextToken)
            ->json('POST', '/api/v1/exports', ['vaults' => [$stamford->uuid]])
            ->assertNotFound();

        $this->assertDatabaseCount('account_exports', 0);
    }
}
