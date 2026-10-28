<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ReadMonicaFile;
use App\Actions\ReadMonicaFourFile;
use App\Actions\StartAccountImport;
use App\Enums\AccountImportStatusEnum;
use App\Exceptions\ImportExceedsALimit;
use App\Jobs\CreateDefaultTemplate;
use App\Jobs\WriteAccountImportBatch;
use App\Models\AccountImport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartAccountImportTest extends TestCase
{
    use RefreshDatabase;

    private User $michael;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
        config(['monica.self_hosted' => true]);

        $this->michael = User::factory()->create();
        new CreateDefaultTemplate(user: $this->michael)->handle();
    }

    #[Test]
    public function it_creates_the_vault_it_was_asked_for_and_starts_writing(): void
    {
        $import = $this->readImport();

        new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin', meSourceUuid: '10000000-0000-4000-8000-000000000001')->execute();

        $import->refresh();
        $vault = Vault::query()->findOrFail($import->vault_id);

        $this->assertSame('Dunder Mifflin', $vault->name);
        $this->assertSame([$vault->id], $import->created_vault_ids);
        $this->assertSame(AccountImportStatusEnum::Writing, $import->status);
        $this->assertSame(23, $import->items_total);
        $this->assertTrue($import->items()->where('kind', 'me')->where('source_uuid', '10000000-0000-4000-8000-000000000001')->exists());
        Queue::assertPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_writes_into_a_vault_the_account_already_has(): void
    {
        $import = $this->readImport();
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id]);

        new StartAccountImport(user: $this->michael, import: $import, vault: $scranton)->execute();

        $import->refresh();

        $this->assertSame($scranton->id, $import->vault_id);
        $this->assertSame([], $import->created_vault_ids);
        $this->assertNull($import->me_source_uuid);
    }

    #[Test]
    public function it_refuses_a_file_that_would_cross_the_contact_limit_and_says_by_how_much(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 3]);
        $import = $this->readImport();

        try {
            new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin')->execute();
            $this->fail('The import was not refused.');
        } catch (ImportExceedsALimit $exception) {
            $this->assertStringContainsString('4 contacts', $exception->getMessage());
            $this->assertStringContainsString('3', $exception->getMessage());
        }

        $this->assertSame(0, Vault::query()->where('account_id', $this->michael->account_id)->count());
        Queue::assertNotPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_leaves_the_contact_chosen_as_me_out_of_the_contact_limit(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 3]);
        $import = $this->readImport();

        new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin', meSourceUuid: '10000000-0000-4000-8000-000000000001')->execute();

        $this->assertSame(AccountImportStatusEnum::Writing, $import->refresh()->status);
        Queue::assertPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_refuses_a_new_vault_the_free_plan_has_no_room_for(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_vault_limit' => 1, 'monica.free_contact_limit' => 100]);
        Vault::factory()->create(['account_id' => $this->michael->account_id]);
        $import = $this->readImport();

        $this->expectException(ImportExceedsALimit::class);

        new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin')->execute();
    }

    #[Test]
    public function it_leaves_each_vault_of_a_file_of_several_to_be_created_from_the_file(): void
    {
        $import = $this->readImportOfTwoVaults();

        new StartAccountImport(user: $this->michael, import: $import)->execute();

        $import->refresh();

        $this->assertNull($import->vault_id);
        $this->assertSame([], $import->created_vault_ids);
        $this->assertSame(0, Vault::query()->where('account_id', $this->michael->account_id)->count());
        $this->assertSame(2, $import->items()->where('kind', 'me')->count());
        $this->assertSame(AccountImportStatusEnum::Writing, $import->status);
        Queue::assertPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_counts_every_vault_of_a_file_of_several_against_the_vault_limit_and_names_the_numbers(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_vault_limit' => 2, 'monica.free_contact_limit' => 100]);
        Vault::factory()->create(['account_id' => $this->michael->account_id]);
        $import = $this->readImportOfTwoVaults();

        try {
            new StartAccountImport(user: $this->michael, import: $import)->execute();
            $this->fail('The import was not refused.');
        } catch (ImportExceedsALimit $exception) {
            $this->assertSame('This file holds 2 vaults and the account already holds 1, more than the 2 the free plan allows. Import into a vault you have, or get full access.', $exception->getMessage());
        }
    }

    #[Test]
    public function it_counts_the_copy_each_avatar_makes_against_the_storage_limit(): void
    {
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 100, 'monica.free_vault_limit' => 10]);
        $import = $this->readImport();
        $files = $import->items()->where('kind', 'file')->get()->sum(fn ($item): int => Storage::disk('local')->size($item->payload['path']));
        $this->michael->account->update(['storage_limit_in_bytes' => $files, 'storage_used_in_bytes' => 0]);

        $this->expectException(ImportExceedsALimit::class);

        new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin')->execute();
    }

    #[Test]
    public function it_refuses_an_import_another_request_started_after_this_one_was_asked(): void
    {
        $import = $this->readImport();
        AccountImport::query()->whereKey($import->id)->update(['status' => AccountImportStatusEnum::Writing]);

        try {
            new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin', meSourceUuid: '10000000-0000-4000-8000-000000000001')->execute();
            $this->fail('The import was started twice.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('This import is not waiting for a decision any more.', $exception->getMessage());
        }

        $this->assertSame(0, Vault::query()->where('account_id', $this->michael->account_id)->count());
        $this->assertFalse($import->items()->where('kind', 'me')->exists());
        Queue::assertNotPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_refuses_a_vault_the_account_has_for_a_file_of_several(): void
    {
        $import = $this->readImportOfTwoVaults();
        $scranton = Vault::factory()->create(['account_id' => $this->michael->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new StartAccountImport(user: $this->michael, import: $import, vault: $scranton)->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $import = $this->readImport();
        $stamford = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new StartAccountImport(user: $this->michael, import: $import, vault: $stamford)->execute();
    }

    #[Test]
    public function it_refuses_an_import_that_is_not_waiting_for_a_decision(): void
    {
        $import = $this->readImport();
        $import->update(['status' => AccountImportStatusEnum::Writing]);

        $this->expectException(InvalidArgumentException::class);

        new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin')->execute();
    }

    #[Test]
    public function it_refuses_a_contact_chosen_as_me_that_the_file_does_not_hold(): void
    {
        $import = $this->readImport();

        $this->expectException(InvalidArgumentException::class);

        new StartAccountImport(user: $this->michael, import: $import, vaultName: 'Dunder Mifflin', meSourceUuid: 'toby-flenderson')->execute();
    }

    private function readImport(): AccountImport
    {
        $import = AccountImport::factory()->create(['user_id' => $this->michael->id]);
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);

        return $import;
    }

    private function readImportOfTwoVaults(): AccountImport
    {
        $vault = fn (string $uuid, string $name, string $me): array => [
            'uuid' => $uuid,
            'name' => $name,
            'description' => null,
            'colour' => 'blue',
            'contacts' => [['uuid' => $me, 'created_at' => '2026-01-01T00:00:00Z', 'is_me' => true, 'is_partial' => false, 'is_favourite' => false, 'is_archived' => false, 'name_representations' => [], 'values' => []]],
            'entities' => [],
            'relationships' => [],
            'life_events' => [],
            'reminders' => [],
            'files' => [],
        ];

        $import = AccountImport::factory()->create(['user_id' => $this->michael->id]);
        Storage::disk('local')->put($import->path, json_encode([
            'version' => '2.0-preview.1',
            'account' => [
                'structures' => [],
                'life_event_types' => [],
                'relationship_types' => [],
                'vaults' => [
                    $vault('20000000-0000-4000-8000-000000000001', 'Scranton', '20000000-0000-4000-8000-000000000011'),
                    $vault('20000000-0000-4000-8000-000000000002', 'Stamford', '20000000-0000-4000-8000-000000000012'),
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        new ReadMonicaFile(import: $import)->execute();
        $import->refresh()->update(['status' => AccountImportStatusEnum::Read]);

        return $import;
    }
}
