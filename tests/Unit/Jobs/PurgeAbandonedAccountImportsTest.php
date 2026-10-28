<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AccountImportStatusEnum;
use App\Jobs\PurgeAbandonedAccountImports;
use App\Jobs\RollBackAccountImportBatch;
use App\Models\AccountImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeAbandonedAccountImportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_discards_an_import_read_a_week_ago_and_never_started_with_its_files(): void
    {
        Storage::fake('local');

        $abandoned = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Read, 'updated_at' => now()->subDays(8)]);
        $waiting = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Read]);
        Storage::disk('local')->put("{$abandoned->directory()}/files/dundie.jpg", 'dundie');

        new PurgeAbandonedAccountImports()->handle();

        $this->assertModelMissing($abandoned);
        $this->assertModelExists($waiting);
        Storage::disk('local')->assertMissing("{$abandoned->directory()}/files/dundie.jpg");
    }

    #[Test]
    public function it_discards_an_import_whose_reading_died_a_day_ago(): void
    {
        Storage::fake('local');

        $stuck = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Reading, 'created_at' => now()->subHours(25)]);
        $reading = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Reading]);

        new PurgeAbandonedAccountImports()->handle();

        $this->assertModelMissing($stuck);
        $this->assertModelExists($reading);
    }

    #[Test]
    public function it_hands_to_the_rollback_an_import_whose_writing_or_rollback_stopped_a_day_ago(): void
    {
        Queue::fake();

        $stuckWriting = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Writing, 'updated_at' => now()->subHours(25)]);
        $stuckRollingBack = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RollingBack, 'updated_at' => now()->subHours(25)]);
        $writing = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Writing]);

        new PurgeAbandonedAccountImports()->handle();

        $this->assertSame(AccountImportStatusEnum::RollingBack, $stuckWriting->refresh()->status);
        $this->assertSame(AccountImportStatusEnum::RollingBack, $stuckRollingBack->refresh()->status);
        $this->assertSame(AccountImportStatusEnum::Writing, $writing->refresh()->status);
        Queue::assertPushed(RollBackAccountImportBatch::class, fn (RollBackAccountImportBatch $job): bool => $job->import->is($stuckWriting));
        Queue::assertPushed(RollBackAccountImportBatch::class, fn (RollBackAccountImportBatch $job): bool => $job->import->is($stuckRollingBack));
        Queue::assertPushed(RollBackAccountImportBatch::class, 2);
    }

    #[Test]
    public function it_leaves_an_import_that_was_written(): void
    {
        $completed = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Completed, 'updated_at' => now()->subMonth()]);

        new PurgeAbandonedAccountImports()->handle();

        $this->assertModelExists($completed);
    }
}
