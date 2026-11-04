<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportSourceEnum;
use App\Enums\AccountImportStatusEnum;
use App\Jobs\DetectDuplicateContacts;
use App\Jobs\RollBackAccountImportBatch;
use App\Jobs\WriteAccountImportBatch;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Vault;
use App\Notifications\AccountImportEnded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WriteAccountImportBatchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_one_batch_and_hands_the_rest_to_the_next_run(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(WriteAccountImportBatch::SIZE + 10);

        new WriteAccountImportBatch(import: $import)->handle();

        $import->refresh();

        $this->assertSame(10, $import->items()->whereNull('outcome')->count());
        $this->assertSame(WriteAccountImportBatch::SIZE, $import->items_done);
        $this->assertSame(AccountImportReasonEnum::ContactNotInTheFile->value, $import->items()->whereNotNull('outcome')->value('reason'));
        Queue::assertPushed(WriteAccountImportBatch::class, 1);
    }

    #[Test]
    public function it_never_writes_an_item_twice_when_a_run_starts_again(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(3);
        $import->items()->limit(2)->get()->each->update(['outcome' => AccountImportOutcomeEnum::Imported]);

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(2, $import->items()->where('outcome', AccountImportOutcomeEnum::Imported)->count());
        $this->assertSame(1, $import->items()->where('outcome', AccountImportOutcomeEnum::LeftOut)->count());
    }

    #[Test]
    public function it_hands_an_import_being_rolled_back_to_its_rollback_without_writing(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(3);
        $import->update(['status' => AccountImportStatusEnum::RollingBack]);

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(3, $import->items()->whereNull('outcome')->count());
        Queue::assertPushed(RollBackAccountImportBatch::class, 1);
        Queue::assertNotPushed(WriteAccountImportBatch::class);
    }

    #[Test]
    public function it_stops_between_two_items_once_the_import_is_cancelled(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(3);
        AccountImportItem::saved(function () use ($import): void {
            AccountImport::query()->whereKey($import->id)->update(['status' => AccountImportStatusEnum::RollingBack->value]);
        });

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(2, $import->items()->whereNull('outcome')->count());
        Queue::assertPushed(RollBackAccountImportBatch::class, 1);
    }

    #[Test]
    public function it_does_not_complete_an_import_cancelled_during_its_last_item(): void
    {
        Queue::fake();
        Notification::fake();
        $import = $this->writingImportOf(1);
        AccountImportItem::saved(function () use ($import): void {
            AccountImport::query()->whereKey($import->id)->update(['status' => AccountImportStatusEnum::RollingBack->value]);
        });

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::RollingBack, $import->refresh()->status);
        Notification::assertNothingSent();
        Queue::assertPushed(RollBackAccountImportBatch::class, 1);
    }

    #[Test]
    public function it_writes_nothing_while_another_run_holds_the_import(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(3);
        $lock = WriteAccountImportBatch::lockOf($import);
        $lock->get();

        new WriteAccountImportBatch(import: $import)->handle();

        $lock->release();
        $this->assertSame(3, $import->items()->whereNull('outcome')->count());
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_counts_in_the_email_only_what_came_over_as_the_report_does(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake('local');
        $import = $this->writingImportOf(0);

        foreach ([
            ['contact', AccountImportOutcomeEnum::Imported],
            ['contact', AccountImportOutcomeEnum::MergedIntoMe],
            ['note', AccountImportOutcomeEnum::LeftOut],
            ['archive', AccountImportOutcomeEnum::Imported],
            ['me', AccountImportOutcomeEnum::MergedIntoMe],
        ] as $position => [$kind, $outcome]) {
            AccountImportItem::factory()->create([
                'account_id' => $import->account_id,
                'account_import_id' => $import->id,
                'kind' => $kind,
                'position' => $position + 1,
                'outcome' => $outcome,
            ]);
        }

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->refresh()->status);
        Notification::assertSentTo($import->user, AccountImportEnded::class, fn (AccountImportEnded $notification): bool => $notification->imported === 2 && $notification->leftOut === 1);
    }

    #[Test]
    public function it_deletes_the_working_files_of_the_import_once_it_completes(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake('local');
        $import = $this->writingImportOf(0);
        Storage::disk('local')->put("{$import->directory()}/files/scan.pdf", 'scan');

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->refresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles($import->directory()));
    }

    #[Test]
    public function it_looks_for_duplicates_once_in_the_vault_it_wrote_into_when_it_completes(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake('local');
        $import = $this->writingImportOf(0);
        $scranton = Vault::factory()->create(['account_id' => $import->account_id]);
        $import->update(['vault_id' => $scranton->id]);

        new WriteAccountImportBatch(import: $import)->handle();

        $this->assertSame(AccountImportStatusEnum::Completed, $import->refresh()->status);
        Queue::assertPushed(DetectDuplicateContacts::class, 1);
        Queue::assertPushedOn('low', DetectDuplicateContacts::class, fn (DetectDuplicateContacts $job): bool => $job->uniqueId() === $scranton->getMorphClass().':'.$scranton->id);
    }

    #[Test]
    public function it_hands_an_import_whose_run_failed_to_its_rollback(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(1);

        new WriteAccountImportBatch(import: $import)->failed(null);

        $this->assertSame(AccountImportStatusEnum::RollingBack, $import->refresh()->status);
        Queue::assertPushed(RollBackAccountImportBatch::class);
    }

    #[Test]
    public function it_leaves_an_import_that_had_completed_when_a_run_failed(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(1);
        $import->update(['status' => AccountImportStatusEnum::Completed]);

        new WriteAccountImportBatch(import: $import)->failed(null);

        $this->assertSame(AccountImportStatusEnum::Completed, $import->refresh()->status);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_stops_a_copy_of_a_run_the_import_no_longer_names(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(3);
        $import->update(['writing_run' => (string) Str::uuid()]);

        new WriteAccountImportBatch(import: $import, run: (string) Str::uuid())->handle();

        $this->assertSame(3, $import->items()->whereNull('outcome')->count());
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_names_the_next_run_on_the_import_when_it_hands_over(): void
    {
        Queue::fake();
        $import = $this->writingImportOf(WriteAccountImportBatch::SIZE + 1);

        new WriteAccountImportBatch(import: $import)->handle();

        $run = $import->refresh()->writing_run;

        $this->assertNotNull($run);
        Queue::assertPushed(WriteAccountImportBatch::class, fn (WriteAccountImportBatch $job): bool => $job->run === $run);
    }

    private function writingImportOf(int $items): AccountImport
    {
        $import = AccountImport::factory()->create([
            'status' => AccountImportStatusEnum::Writing,
            'source_format' => AccountImportSourceEnum::MonicaFour,
            'items_total' => $items,
        ]);

        for ($position = 1; $position <= $items; $position++) {
            AccountImportItem::factory()->create([
                'account_id' => $import->account_id,
                'account_import_id' => $import->id,
                'kind' => 'archive',
                'position' => $position,
                'payload' => ['contact' => 'toby-flenderson'],
                'outcome' => null,
            ]);
        }

        return $import;
    }
}
