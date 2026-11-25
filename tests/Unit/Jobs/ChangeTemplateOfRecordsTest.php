<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateTemplateChange;
use App\Helpers\BulkTemplateChangePreview;
use App\Helpers\RecordKind;
use App\Helpers\TemplateChangeBatch;
use App\Jobs\ChangeTemplateOfRecords;
use App\Models\Account;
use App\Models\Entity;
use App\Models\FieldValue;
use App\Models\TemplateChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Statistics\AnimalsVault;
use Tests\TestCase;

class ChangeTemplateOfRecordsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_every_record_of_the_slice_as_the_preview_announced(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $preview = $this->preview($fixture);
        $valuesBefore = FieldValue::query()->count();
        $change = $this->confirm($fixture);

        $this->runSlices($change);

        $this->assertSame(4, Entity::query()->where('structure_version_id', $fixture->petV3->id)->whereIn('id', collect($fixture->horses)->pluck('id'))->count());
        $this->assertSame($valuesBefore, FieldValue::query()->count());
        $this->assertSame(
            $preview['counts']['setAside'],
            FieldValue::query()->where('field_id', $fixture->fields['stable_name']->id)->where('is_detached', true)->count(),
        );
        $this->assertDatabaseHas('reminders', ['id' => $fixture->farrierVisit->id, 'is_active' => false]);
        $this->assertDatabaseCount('record_snapshots', 4);
        $this->assertDatabaseHas('template_changes', ['id' => $change->id, 'moved_count' => 4, 'skipped_count' => 0, 'failed_count' => 0]);
    }

    #[Test]
    public function it_skips_a_record_whose_template_changed_after_the_preview(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = $this->confirm($fixture);
        $fixture->horses['Bijou']->update(['structure_version_id' => $fixture->birdV1->id]);

        $this->runSlices($change);

        $this->assertDatabaseHas('template_change_items', [
            'template_change_id' => $change->id,
            'record_id' => $fixture->horses['Bijou']->id,
            'outcome' => 'skipped',
            'reason' => 'changed_after_preview',
        ]);
        $this->assertSame($fixture->birdV1->id, $fixture->horses['Bijou']->refresh()->structure_version_id);
        $this->assertDatabaseHas('template_changes', ['id' => $change->id, 'moved_count' => 3, 'skipped_count' => 1]);
    }

    #[Test]
    public function it_goes_on_with_the_others_when_one_record_fails(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = $this->confirm($fixture);
        $fixture->horses['Ulysse']->updateQuietly(['account_id' => Account::factory()->create()->id]);

        $this->runSlices($change);

        $this->assertDatabaseHas('template_change_items', ['record_id' => $fixture->horses['Ulysse']->id, 'outcome' => 'failed']);
        $this->assertSame($fixture->horseV1->id, $fixture->horses['Ulysse']->refresh()->structure_version_id);
        $this->assertDatabaseHas('template_changes', ['id' => $change->id, 'moved_count' => 3, 'failed_count' => 1]);
    }

    #[Test]
    public function it_moves_nothing_more_when_the_slice_runs_again(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = $this->confirm($fixture);

        $this->runSlices($change);
        $endedAt = $change->refresh()->ended_at;
        $this->runSlices($change);

        $this->assertDatabaseCount('record_snapshots', 4);
        $this->assertEquals($endedAt, $change->refresh()->ended_at);
        $this->assertFalse($change->isRunning());
    }

    #[Test]
    public function it_stays_running_while_another_slice_has_records_left(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = $this->confirm($fixture);
        $firstItem = $change->items()->orderBy('id')->firstOrFail();

        new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $firstItem->id, lastItemId: $firstItem->id)->handle();

        $this->assertTrue($change->refresh()->isRunning());
        $this->assertSame(3, $change->items()->whereNull('outcome')->count());
    }

    #[Test]
    public function it_counts_a_record_already_on_the_destination_as_moved_when_a_slice_is_picked_up_again(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = $this->confirm($fixture);
        $fixture->horses['Bijou']->update(['structure_version_id' => $fixture->petV3->id]);

        $this->runSlices($change);

        $this->assertDatabaseHas('template_change_items', ['record_id' => $fixture->horses['Bijou']->id, 'outcome' => 'moved', 'reason' => null]);
        $this->assertDatabaseHas('template_changes', ['id' => $change->id, 'moved_count' => 4, 'skipped_count' => 0]);
    }

    #[Test]
    public function it_marks_what_is_left_of_the_slice_failed_and_ends_the_change_once_its_attempts_are_spent(): void
    {
        Queue::fake();
        $fixture = AnimalsVault::build();
        $change = $this->confirm($fixture);
        $ids = $change->items()->orderBy('id')->pluck('id');

        new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $ids->first(), lastItemId: $ids->first())->handle();
        new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $ids->first(), lastItemId: $ids->last())->failed(null);

        $this->assertFalse($change->refresh()->isRunning());
        $this->assertDatabaseHas('template_changes', ['id' => $change->id, 'moved_count' => 1, 'failed_count' => 3]);
        $this->assertSame(0, $change->items()->whereNull('outcome')->count());
    }

    private function confirm(AnimalsVault $fixture): TemplateChange
    {
        return new CreateTemplateChange(
            user: $fixture->user,
            kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
            source: $fixture->horse,
            versionNumbers: [],
            target: $fixture->pet,
        )->execute();
    }

    private function runSlices(TemplateChange $change): void
    {
        $ids = $change->items()->orderBy('id')->pluck('id');

        new ChangeTemplateOfRecords(templateChange: $change, firstItemId: $ids->first(), lastItemId: $ids->last())->handle();
    }

    /** @return array<string, mixed> */
    private function preview(AnimalsVault $fixture): array
    {
        return new BulkTemplateChangePreview(
            batch: new TemplateChangeBatch(
                kind: RecordKind::forEntityType(vault: $fixture->vault, entityType: $fixture->animals),
                source: $fixture->horse,
                versionNumbers: [],
                target: $fixture->petV3,
            ),
            namesSensitiveFields: true,
        )->toArray();
    }
}
