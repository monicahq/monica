<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\RecordKind;
use App\Helpers\TemplateChangeBatch;
use App\Jobs\ChangeTemplateOfRecords;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Confirm a change of template for many records of one kind in a vault: write the batch and every
 * record it takes, then hand the moving to the queue in slices that run side by side.
 */
class CreateTemplateChange
{
    public const int ITEMS_PER_INSERT = 1000;

    private TemplateChange $templateChange;

    private TemplateChangeBatch $batch;

    /** @param list<int> $versionNumbers */
    public function __construct(
        private readonly User $user,
        private readonly RecordKind $kind,
        private readonly Template $source,
        private readonly array $versionNumbers,
        private readonly Template $target,
    ) {}

    public function execute(): TemplateChange
    {
        $this->validate();
        $this->create();
        $this->dispatch();
        $this->log();

        return $this->templateChange;
    }

    public static function refusal(RecordKind $kind, Template $source, Template $target): ?string
    {
        if (! $kind->describes($source) || ! $kind->describes($target)) {
            return __('This template describes another kind of record.');
        }

        if (! $target->is_active) {
            return __('This template is turned off, so no record can be put on it.');
        }

        if ($target->structure->published_version_id === null) {
            return __('This template has no published version yet.');
        }

        return null;
    }

    private function validate(): void
    {
        if ($this->kind->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->source->account_id !== $this->user->account_id || $this->target->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This template is in another account.');
        }

        $refusal = self::refusal($this->kind, $this->source, $this->target);

        if ($refusal !== null) {
            throw new InvalidArgumentException($refusal);
        }
    }

    private function create(): void
    {
        $morph = $this->kind->morphClass();

        $this->templateChange = DB::transaction(function () use ($morph): TemplateChange {
            Vault::query()->whereKey($this->kind->vault->id)->lockForUpdate()->first();

            $this->batch = new TemplateChangeBatch(
                kind: $this->kind,
                source: $this->source,
                versionNumbers: $this->versionNumbers,
                target: $this->target->structure->publishedVersion,
            );

            $records = $this->batch->movable();

            if ($records->isEmpty()) {
                throw new InvalidArgumentException(__('No record is left to move.'));
            }

            $templateChange = TemplateChange::query()->create([
                'account_id' => $this->user->account_id,
                'vault_id' => $this->kind->vault->id,
                'user_id' => $this->user->id,
                'entity_type_id' => $this->kind->entityType?->id,
                'from_template_id' => $this->source->id,
                'from_version_ids' => $this->batch->sourceVersionIds(),
                'structure_version_id' => $this->batch->target->id,
                'items_total' => $records->count(),
            ]);

            $now = now();

            foreach ($records->chunk(self::ITEMS_PER_INSERT) as $chunk) {
                TemplateChangeItem::query()->insert($chunk->map(fn (object $record): array => [
                    'account_id' => $this->user->account_id,
                    'template_change_id' => $templateChange->id,
                    'record_type' => $morph,
                    'record_id' => $record->id,
                    'from_version_id' => $record->structure_version_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all());
            }

            return $templateChange;
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::TemplateChangeStarted,
            vault: $this->kind->vault,
            parameters: [
                'count' => $this->templateChange->items_total,
                'template' => $this->target->name,
            ],
            loggable: $this->templateChange,
        );
    }

    private function dispatch(): void
    {
        $this->templateChange->items()
            ->orderBy('id')
            ->pluck('id')
            ->chunk(ChangeTemplateOfRecords::SIZE)
            ->each(fn ($ids) => ChangeTemplateOfRecords::dispatch(
                templateChange: $this->templateChange,
                firstItemId: (int) $ids->first(),
                lastItemId: (int) $ids->last(),
            ));
    }
}
