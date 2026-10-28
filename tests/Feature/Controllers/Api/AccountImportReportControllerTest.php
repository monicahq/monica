<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportReportControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'kind',
            'source_uuid',
            'reason',
        ],
    ];

    #[Test]
    public function it_lists_what_was_left_out_and_why(): void
    {
        $michael = User::factory()->create();
        $import = AccountImport::factory()->create([
            'user_id' => $michael->id,
            'status' => AccountImportStatusEnum::Completed,
            'ended_at' => now(),
        ]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::Imported]);
        AccountImportItem::factory()->create([
            'account_import_id' => $import->id,
            'kind' => 'reminder',
            'source_uuid' => '30000000-0000-4000-8000-000000000001',
            'outcome' => AccountImportOutcomeEnum::LeftOut,
            'reason' => AccountImportReasonEnum::OneTimeReminderInThePast->value,
        ]);
        Sanctum::actingAs($michael);

        $response = $this->json('GET', "/api/v1/imports/{$import->uuid}/report");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.kind', 'reminder');
        $response->assertJsonPath('data.0.attributes.source_uuid', '30000000-0000-4000-8000-000000000001');
        $response->assertJsonPath('data.0.attributes.reason', 'A reminder for a single date that has already passed.');
        $response->assertJsonPath('meta.total', 1);
    }

    #[Test]
    public function it_answers_not_found_for_an_import_of_another_account(): void
    {
        $import = AccountImport::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/imports/{$import->uuid}/report")->assertNotFound();
    }
}
