<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\TemplateChangeOutcomeEnum;
use App\Helpers\RecordKind;
use App\Models\TemplateChange;
use App\Models\TemplateChangeItem;
use App\Models\User;
use App\Models\Vault;

class TemplateChangeViewModel
{
    public const int NOT_MOVED_SHOWN = 100;

    public function __construct(
        private readonly User $user,
        private readonly RecordKind $kind,
        private readonly TemplateChange $templateChange,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->kind->vault;
    }

    public function sidebarCurrent(): string
    {
        return $this->kind->isPeople() ? 'contacts' : "entity-type-{$this->kind->entityType->slug}";
    }

    public function title(): string
    {
        return __(':from → :to', ['from' => $this->templateChange->fromTemplate->name, 'to' => $this->toName()]);
    }

    /** @return list<array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => $this->kind->title(), 'url' => $this->kind->listUrl()],
            ['label' => __('Statistics'), 'url' => $this->kind->route('statistics.index')],
            ['label' => __('Change template')],
        ];
    }

    /** @return list<string> */
    public function steps(): array
    {
        return [__('Select :name', ['name' => $this->kind->plural()]), __('Preview'), __('Result')];
    }

    public function isRunning(): bool
    {
        return $this->templateChange->isRunning();
    }

    public function progressPercent(): int
    {
        $total = $this->templateChange->items_total;

        return $total === 0 ? 100 : (int) floor($this->done() * 100 / $total);
    }

    public function progressSentence(): string
    {
        return trans_choice(':done of :count :singular dealt with so far.|:done of :count :plural dealt with so far.', $this->templateChange->items_total, [
            ...$this->names(),
            'done' => $this->done(),
        ]);
    }

    public function summary(): string
    {
        $parts = [trans_choice(':count :singular moved|:count :plural moved', $this->templateChange->moved_count, $this->names())];

        if ($this->templateChange->skipped_count > 0) {
            $parts[] = trans_choice(':count skipped|:count skipped', $this->templateChange->skipped_count);
        }

        if ($this->templateChange->failed_count > 0) {
            $parts[] = trans_choice(':count failed|:count failed', $this->templateChange->failed_count);
        }

        return implode(' · ', $parts);
    }

    public function recountSentence(): string
    {
        if ($this->templateChange->moved_count === 0) {
            return __('No :name moved, so the statistics are as they were.', ['name' => $this->kind->singular()]);
        }

        return trans_choice('Statistics now count the :count :singular that moved.|Statistics now count the :count :plural that moved.', $this->templateChange->moved_count, $this->names());
    }

    public function failedSentence(): string
    {
        return trans_choice('[0,*]Failed: :count', $this->templateChange->failed_count);
    }

    /** @return list<array{name: string, outcome: string, reason: string}> */
    public function notMoved(): array
    {
        return $this->templateChange->items()
            ->whereIn('outcome', [TemplateChangeOutcomeEnum::Skipped, TemplateChangeOutcomeEnum::Failed])
            ->with('record')
            ->orderBy('id')
            ->limit(self::NOT_MOVED_SHOWN)
            ->get()
            ->map(fn (TemplateChangeItem $item): array => [
                'name' => $item->record->name ?? __('A record that was deleted'),
                'outcome' => $item->outcome === TemplateChangeOutcomeEnum::Skipped ? __('Skipped') : __('Failed'),
                'reason' => $item->reason?->sentence($this->kind->singular()) ?? '',
            ])
            ->all();
    }

    public function viewAffectedUrl(): string
    {
        return $this->kind->listUrl(['change' => $this->templateChange->uuid]);
    }

    public function viewAffectedLabel(): string
    {
        return __('View affected :name', ['name' => $this->kind->plural()]);
    }

    public function backUrl(): string
    {
        return $this->kind->route('statistics.index');
    }

    public function refreshUrl(): string
    {
        return $this->kind->route('templateChanges.show', ['templateChange' => $this->templateChange->uuid]);
    }

    private function toName(): string
    {
        $target = $this->templateChange->structureVersion;

        return __(':template v:number', ['template' => $target->structure->owner->name, 'number' => $target->version_number]);
    }

    private function done(): int
    {
        return $this->templateChange->items()->whereNotNull('outcome')->count();
    }

    /** @return array<string, string> */
    private function names(): array
    {
        return ['singular' => $this->kind->singular(), 'plural' => $this->kind->plural()];
    }
}
