<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\MoveRecordToStructureVersion;
use App\Enums\TemplateChangeOutcomeEnum;
use App\Enums\TemplateChangeReasonEnum;
use App\Helpers\FieldCorrespondence;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Move one slice of a change of template, each record through the same move a single record takes,
 * and close the change once nothing is left waiting. It is queued so that slices of a batch of
 * thousands run side by side, and an item with an outcome is never moved again.
 *
 * The timeout stays under the queue's `retry_after`, so a slice is never handed to a second worker
 * while the first is still on it. A slice cut short is picked up where it stopped, a record found
 * already on the destination counts as moved, and once the attempts are spent whatever is left is
 * marked failed, so the change always ends and never holds its records.
 */
class ChangeTemplateOfRecords implements ShouldQueue
{
    use Queueable;

    public const int SIZE = 200;

    public int $timeout = 75;

    public int $tries = 3;

    public function __construct(
        public TemplateChange $templateChange,
        public int $firstItemId,
        public int $lastItemId,
    ) {}

    public function handle(): void
    {
        if (! $this->templateChange->refresh()->isRunning()) {
            return;
        }

        $items = $this->templateChange->items()
            ->whereBetween('id', [$this->firstItemId, $this->lastItemId])
            ->whereNull('outcome')
            ->get();

        if ($items->isNotEmpty()) {
            $this->move($items);
        }

        $this->finishWhenNothingIsLeft();
    }

    public function failed(?Throwable $exception): void
    {
        $this->templateChange->items()
            ->whereBetween('id', [$this->firstItemId, $this->lastItemId])
            ->whereNull('outcome')
            ->update([
                'outcome' => TemplateChangeOutcomeEnum::Failed,
                'reason' => TemplateChangeReasonEnum::MoveFailed,
                'updated_at' => now(),
            ]);

        $this->finishWhenNothingIsLeft();
    }

    /** @param Collection<int, TemplateChangeItem> $items */
    private function move(Collection $items): void
    {
        /** @var class-string<Contact|Entity> $class */
        $class = $items->first()->record_type;
        $records = $class::query()->withTrashed()->whereIn('id', $items->pluck('record_id'))->get()->keyBy('id');
        $correspondence = new FieldCorrespondence($this->templateChange->structureVersion);
        $moved = [];

        $class::withoutSyncingToSearch(function () use ($items, $records, $correspondence, &$moved): void {
            foreach ($items as $item) {
                [$outcome, $reason] = $this->moveOne($records->get($item->record_id), $item, $correspondence);

                $item->update(['outcome' => $outcome, 'reason' => $reason]);

                if ($outcome === TemplateChangeOutcomeEnum::Moved) {
                    $moved[] = $item->record_id;
                }
            }
        });

        $reindexed = $records->only($moved);

        if ($reindexed->isNotEmpty()) {
            $reindexed->first()->queueMakeSearchable($reindexed);
        }
    }

    /** @return array{0: TemplateChangeOutcomeEnum, 1: TemplateChangeReasonEnum|null} */
    private function moveOne(Contact|Entity|null $record, TemplateChangeItem $item, FieldCorrespondence $correspondence): array
    {
        if ($record === null || $record->trashed()) {
            return [TemplateChangeOutcomeEnum::Skipped, TemplateChangeReasonEnum::Deleted];
        }

        if ($record->is_archived) {
            return [TemplateChangeOutcomeEnum::Skipped, TemplateChangeReasonEnum::Archived];
        }

        if ($record->structure_version_id === $this->templateChange->structure_version_id) {
            return [TemplateChangeOutcomeEnum::Moved, null];
        }

        if ($record->structure_version_id !== $item->from_version_id) {
            return [TemplateChangeOutcomeEnum::Skipped, TemplateChangeReasonEnum::ChangedAfterPreview];
        }

        if ($this->templateChange->user === null) {
            return [TemplateChangeOutcomeEnum::Failed, TemplateChangeReasonEnum::ConfirmedByDeletedUser];
        }

        try {
            new MoveRecordToStructureVersion(
                user: $this->templateChange->user,
                record: $record,
                structureVersion: $this->templateChange->structureVersion,
                writesToLog: false,
                correspondence: $correspondence,
                reindexes: false,
            )->execute();
        } catch (Throwable $exception) {
            report($exception);

            return [TemplateChangeOutcomeEnum::Failed, TemplateChangeReasonEnum::MoveFailed];
        }

        return [TemplateChangeOutcomeEnum::Moved, null];
    }

    private function finishWhenNothingIsLeft(): void
    {
        if ($this->templateChange->items()->whereNull('outcome')->exists()) {
            return;
        }

        $counts = $this->templateChange->items()
            ->toBase()
            ->selectRaw('outcome, count(*) as aggregate')
            ->groupBy('outcome')
            ->pluck('aggregate', 'outcome');

        TemplateChange::query()
            ->whereKey($this->templateChange->id)
            ->whereNull('ended_at')
            ->update([
                'moved_count' => (int) ($counts[TemplateChangeOutcomeEnum::Moved->value] ?? 0),
                'skipped_count' => (int) ($counts[TemplateChangeOutcomeEnum::Skipped->value] ?? 0),
                'failed_count' => (int) ($counts[TemplateChangeOutcomeEnum::Failed->value] ?? 0),
                'ended_at' => now(),
            ]);
    }
}
