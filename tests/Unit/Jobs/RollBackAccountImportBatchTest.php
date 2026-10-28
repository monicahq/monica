<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AccountImportStatusEnum;
use App\Jobs\RollBackAccountImportBatch;
use App\Jobs\WriteAccountImportBatch;
use App\Models\AccountImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class RollBackAccountImportBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
    }

    #[Test]
    public function it_rolls_back_an_import_being_rolled_back(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RollingBack]);

        new RollBackAccountImportBatch(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::RolledBack, $import->refresh()->status);
    }

    #[Test]
    public function it_leaves_an_import_that_is_not_being_rolled_back(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Writing]);

        new RollBackAccountImportBatch(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::Writing, $import->refresh()->status);
    }

    #[Test]
    public function it_waits_while_a_run_writing_the_import_holds_it(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RollingBack]);
        $lock = WriteAccountImportBatch::lockOf($import);
        $lock->get();

        new RollBackAccountImportBatch(import: $import)->handle();

        $lock->release();
        $this->assertSame(AccountImportStatusEnum::RollingBack, $import->refresh()->status);
    }

    #[Test]
    public function it_ends_an_import_whose_rollback_kept_failing_with_what_it_wrote_in_place(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RollingBack]);

        new RollBackAccountImportBatch(import: $import)->failed(new RuntimeException('The database went away.'));

        $import->refresh();
        $this->assertSame(AccountImportStatusEnum::RollbackFailed, $import->status);
        $this->assertNotNull($import->ended_at);
    }

    #[Test]
    public function it_deletes_the_working_files_of_an_import_whose_rollback_kept_failing(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RollingBack]);
        Storage::disk('local')->put("{$import->directory()}/files/photo", 'Dwight');

        new RollBackAccountImportBatch(import: $import)->failed(new RuntimeException('The database went away.'));

        Storage::disk('local')->assertMissing($import->directory());
    }

    #[Test]
    public function it_leaves_an_import_already_rolled_back_when_a_late_run_fails(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RolledBack]);

        new RollBackAccountImportBatch(import: $import)->failed(new RuntimeException('The database went away.'));

        $this->assertSame(AccountImportStatusEnum::RolledBack, $import->refresh()->status);
    }
}
