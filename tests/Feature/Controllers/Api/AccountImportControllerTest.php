<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\ReadMonicaFourFile;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\ReadAccountImport;
use App\Jobs\WriteAccountImportBatch;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Transfer\AccountTransferViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'status',
            'source_format',
            'refusal',
            'summary',
            'already_imported_into',
            'progress' => ['done', 'total'],
            'vault',
            'me_contact',
            'report',
            'created_at',
            'ended_at',
        ],
        'links' => ['self'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        config(['monica.self_hosted' => true]);

        $this->michael = User::factory()->create();
        new CreateDefaultTemplate(user: $this->michael)->handle();

        Sanctum::actingAs($this->michael);
    }

    #[Test]
    public function it_takes_a_file_and_has_it_read(): void
    {
        $response = $this->json('POST', '/api/v1/imports', [
            'file' => UploadedFile::fake()->createWithContent('monica.json', '{"version": "1.0-preview.1"}'),
        ]);

        $response->assertAccepted();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.status', 'reading');
        Queue::assertPushed(ReadAccountImport::class);
    }

    #[Test]
    public function it_refuses_a_file_over_the_size_cap(): void
    {
        $this->json('POST', '/api/v1/imports', [
            'file' => UploadedFile::fake()->create('monica.json', AccountTransferViewModel::MAXIMUM_UPLOAD_IN_KILOBYTES + 1),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function it_shows_what_a_file_holds(): void
    {
        $import = $this->readImport();

        $response = $this->json('GET', "/api/v1/imports/{$import->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.id', $import->uuid);
        $response->assertJsonPath('data.attributes.status', 'read');
        $response->assertJsonPath('data.attributes.source_format', 'monica_4');
        $response->assertJsonPath('data.attributes.me_contact', '10000000-0000-4000-8000-000000000001');
        $response->assertJsonPath('data.attributes.report', null);
        $this->assertContains(['kind' => 'contacts', 'found' => 5, 'left_out' => 0], $response->json('data.attributes.summary'));
    }

    #[Test]
    public function it_shows_the_report_of_an_import_that_ended_and_where_the_file_went_before(): void
    {
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Scranton']);
        $stamford = Vault::factory()->create(['account_id' => $this->michael->account_id, 'name' => 'Stamford']);
        $import = AccountImport::factory()->create([
            'user_id' => $this->michael->id,
            'status' => AccountImportStatusEnum::Completed,
            'vault_id' => $stamford->id,
            'ended_at' => now(),
        ]);
        AccountImport::factory()->create([
            'user_id' => $this->michael->id,
            'status' => AccountImportStatusEnum::Completed,
            'source_checksum' => $import->source_checksum,
            'vault_id' => $scranton->id,
        ]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::Imported]);
        AccountImportItem::factory()->create([
            'account_import_id' => $import->id,
            'kind' => 'contact',
            'outcome' => AccountImportOutcomeEnum::LeftOut,
            'reason' => AccountImportReasonEnum::RefusedWhileWriting->value,
        ]);

        $response = $this->json('GET', "/api/v1/imports/{$import->uuid}");

        $response->assertOk();
        $response->assertJsonPath('data.attributes.vault', $stamford->uuid);
        $response->assertJsonPath('data.attributes.already_imported_into', [$scranton->uuid]);
        $response->assertJsonPath('data.attributes.report', [['kind' => 'contact', 'imported' => 1, 'left_out' => 1]]);
    }

    #[Test]
    public function it_reports_the_kinds_only_counted_while_reading_and_nothing_for_an_import_undone(): void
    {
        $import = AccountImport::factory()->create([
            'user_id' => $this->michael->id,
            'status' => AccountImportStatusEnum::Completed,
            'summary' => ['contacts' => ['found' => 1, 'left_out' => 0], 'audit_logs' => ['found' => 3, 'left_out' => 3]],
            'ended_at' => now(),
        ]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => null]);

        $this->json('GET', "/api/v1/imports/{$import->uuid}")
            ->assertJsonPath('data.attributes.report', [
                ['kind' => 'contact', 'imported' => 0, 'left_out' => 0],
                ['kind' => 'audit_logs', 'imported' => 0, 'left_out' => 3],
            ]);

        $import->update(['status' => AccountImportStatusEnum::RolledBack]);

        $this->json('GET', "/api/v1/imports/{$import->uuid}")
            ->assertJsonPath('data.attributes.report', null);
    }

    #[Test]
    public function it_answers_not_found_for_an_import_of_another_account(): void
    {
        $jan = User::factory()->create();
        $import = AccountImport::factory()->create(['user_id' => $jan->id]);

        $this->json('GET', "/api/v1/imports/{$import->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_starts_writing_into_a_new_vault(): void
    {
        $import = $this->readImport();

        $response = $this->json('PUT', "/api/v1/imports/{$import->uuid}", [
            'vault_name' => 'Dunder Mifflin',
            'me_contact' => '10000000-0000-4000-8000-000000000001',
        ]);

        $response->assertAccepted();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.status', 'writing');
        $response->assertJsonPath('data.attributes.me_contact', '10000000-0000-4000-8000-000000000001');
        $this->assertNotNull($response->json('data.attributes.vault'));
        Queue::assertPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_starts_writing_into_a_vault_of_the_account(): void
    {
        $import = $this->readImport();
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id]);

        $response = $this->json('PUT', "/api/v1/imports/{$import->uuid}", [
            'vault' => $scranton->uuid,
            'me_contact' => null,
        ]);

        $response->assertAccepted();
        $response->assertJsonPath('data.attributes.vault', $scranton->uuid);
        $response->assertJsonPath('data.attributes.me_contact', null);
    }

    #[Test]
    public function it_asks_for_a_vault_or_the_name_of_a_new_one(): void
    {
        $import = $this->readImport();

        $this->json('PUT', "/api/v1/imports/{$import->uuid}", ['me_contact' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vault_name');
    }

    #[Test]
    public function it_answers_a_refusal_from_the_limits_with_its_wording(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 2, 'monica.free_vault_limit' => 5]);
        $import = $this->readImport();

        $this->json('PUT', "/api/v1/imports/{$import->uuid}", ['vault_name' => 'Dunder Mifflin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('import');

        $this->assertSame(AccountImportStatusEnum::Read, $import->refresh()->status);
    }

    #[Test]
    public function it_answers_not_found_for_a_vault_of_another_account(): void
    {
        $import = $this->readImport();
        $stamford = Vault::factory()->create();

        $this->json('PUT', "/api/v1/imports/{$import->uuid}", ['vault' => $stamford->uuid])
            ->assertNotFound();

        $this->assertSame(AccountImportStatusEnum::Read, $import->refresh()->status);
    }

    #[Test]
    public function it_discards_an_import_that_has_not_started_writing(): void
    {
        $import = $this->readImport();

        $this->json('DELETE', "/api/v1/imports/{$import->uuid}")->assertNoContent();

        $this->assertModelMissing($import);
    }

    #[Test]
    public function it_rolls_back_an_import_that_is_writing(): void
    {
        $import = AccountImport::factory()->create([
            'user_id' => $this->michael->id,
            'status' => AccountImportStatusEnum::Writing,
        ]);

        $response = $this->json('DELETE', "/api/v1/imports/{$import->uuid}");

        $response->assertAccepted();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.status', 'rolling_back');
    }

    private function readImport(): AccountImport
    {
        $import = AccountImport::factory()->create(['user_id' => $this->michael->id]);
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read, 'source_format' => AccountImportSourceEnum::MonicaFour]);

        return $import;
    }
}
