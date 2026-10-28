<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\Transfer;

use App\Actions\ReadMonicaFourFile;
use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\ViewModels\Settings\Account\Transfer\AccountImportViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountImportViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_what_the_file_holds_kind_by_kind(): void
    {
        $import = $this->readImport();

        $summary = collect(new AccountImportViewModel(user: $import->user, import: $import)->summary())->keyBy('label');

        $this->assertSame(5, $summary['Contacts']['found']);
        $this->assertSame(1, $summary['History of changes']['left_out']);
    }

    #[Test]
    public function it_lists_the_contacts_of_the_file_by_name_for_choosing_me(): void
    {
        $import = $this->readImport();

        $names = array_column(new AccountImportViewModel(user: $import->user, import: $import)->contactsOfTheFile(), 'name');

        $this->assertSame(['Cece', 'Dwight Schrute', 'Jim Halpert', 'Michael Scott', 'Pam Beesly'], $names);
    }

    #[Test]
    public function it_says_why_each_thing_was_left_out(): void
    {
        $import = $this->readImport();

        $leftOut = new AccountImportViewModel(user: $import->user, import: $import)->leftOut();

        $this->assertCount(2, $leftOut);
        $this->assertSame('Relationships', $leftOut[0]['kind']);
        $this->assertSame(AccountImportReasonEnum::ReverseOfAnotherRelationship->label(), $leftOut[0]['reason']);
    }

    #[Test]
    public function it_says_an_import_that_could_not_be_undone_left_its_records_in_place(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::RollbackFailed, 'ended_at' => now()]);

        $viewModel = new AccountImportViewModel(user: $import->user, import: $import);

        $this->assertSame('The import could not be undone. What it wrote is still in the vault, and can be removed by hand.', $viewModel->statusSentence());
        $this->assertFalse($viewModel->isMoving());
        $this->assertSame([], $viewModel->report());
    }

    #[Test]
    public function it_tells_how_far_writing_has_gone(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Writing, 'items_total' => 40, 'items_done' => 10]);

        $viewModel = new AccountImportViewModel(user: $import->user, import: $import);

        $this->assertSame('Writing 10 of 40.', $viewModel->statusSentence());
        $this->assertSame(25, $viewModel->progressPercent());
        $this->assertTrue($viewModel->isMoving());
    }

    #[Test]
    public function it_counts_what_came_over_once_the_import_has_completed(): void
    {
        $import = $this->readImport();
        $import->update(['status' => AccountImportStatusEnum::Completed]);
        $import->items()->where('kind', 'contact')->limit(4)->update(['outcome' => AccountImportOutcomeEnum::Imported->value]);

        $report = collect(new AccountImportViewModel(user: $import->user, import: $import)->report())->keyBy('label');

        $this->assertSame(4, $report['Contacts']['imported']);
        $this->assertSame(2, $report['Relationships']['left_out']);
        $this->assertSame(['label' => 'History of changes', 'imported' => 0, 'left_out' => 1], $report['History of changes']);
    }

    #[Test]
    public function it_reports_as_imported_only_the_values_written_inside_a_contact(): void
    {
        $import = AccountImport::factory()->create([
            'status' => AccountImportStatusEnum::Completed,
            'summary' => ['notes' => ['found' => 3, 'left_out' => 0]],
        ]);
        AccountImportItem::factory()->create(['account_import_id' => $import->id, 'kind' => 'contact', 'outcome' => AccountImportOutcomeEnum::Imported, 'payload' => ['written' => ['notes' => 2]]]);

        $report = new AccountImportViewModel(user: $import->user, import: $import)->report();
        $notes = end($report);

        $this->assertSame(2, $notes['imported']);
        $this->assertSame(1, $notes['left_out']);
    }

    #[Test]
    public function it_reports_nothing_as_come_over_for_an_import_that_was_undone(): void
    {
        $import = $this->readImport();
        $import->update(['status' => AccountImportStatusEnum::RolledBack]);
        $import->items()->where('kind', 'contact')->update(['outcome' => AccountImportOutcomeEnum::Imported->value]);

        $this->assertSame([], new AccountImportViewModel(user: $import->user, import: $import)->report());
    }

    #[Test]
    public function it_says_why_things_will_not_come_over_before_the_import_starts(): void
    {
        $import = $this->readImport();

        $reasons = collect(new AccountImportViewModel(user: $import->user, import: $import)->reasonsForLeavingOut())->keyBy('reason');

        $this->assertSame(2, $reasons[AccountImportReasonEnum::ReverseOfAnotherRelationship->label()]['count']);
        $this->assertSame(6, $reasons['Nothing of this kind is kept in a vault.']['count']);
    }

    #[Test]
    public function it_says_when_the_list_of_what_was_left_out_is_cut_short(): void
    {
        $import = AccountImport::factory()->create(['status' => AccountImportStatusEnum::Completed]);
        AccountImportItem::factory()->count(AccountImportViewModel::LEFT_OUT_SHOWN)->create([
            'account_import_id' => $import->id,
            'kind' => 'reminder',
            'outcome' => AccountImportOutcomeEnum::LeftOut,
            'reason' => AccountImportReasonEnum::OneTimeReminderInThePast->value,
        ]);
        $viewModel = new AccountImportViewModel(user: $import->user, import: $import);

        $this->assertNull($viewModel->leftOutListNote());

        AccountImportItem::factory()->create([
            'account_import_id' => $import->id,
            'kind' => 'reminder',
            'outcome' => AccountImportOutcomeEnum::LeftOut,
            'reason' => AccountImportReasonEnum::OneTimeReminderInThePast->value,
        ]);

        $this->assertSame('201 things were left out, and the first 200 are listed here.', $viewModel->leftOutListNote());
        $this->assertCount(AccountImportViewModel::LEFT_OUT_SHOWN, $viewModel->leftOut());
    }

    private function readImport(): AccountImport
    {
        Storage::fake('local');

        $import = AccountImport::factory()->create();
        Storage::disk('local')->put($import->path, (string) file_get_contents(base_path('tests/Fixtures/Imports/monica-4-small.json')));

        new ReadMonicaFourFile(import: $import)->execute();

        return $import->refresh();
    }
}
