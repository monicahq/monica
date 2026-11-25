<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\BulkTemplateChangePreview;
use App\Helpers\RecordKind;
use App\Helpers\TemplateChangeBatch;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;

class TemplateChangePreviewViewModel
{
    /** @var array<string, mixed>|null */
    private ?array $preview = null;

    private TemplateChangeBatch $batch;

    public function __construct(
        private readonly User $user,
        private readonly RecordKind $kind,
        private readonly Template $source,
        private readonly ?int $versionNumber,
        private readonly Template $target,
    ) {
        $this->batch = new TemplateChangeBatch(
            kind: $kind,
            source: $source,
            versionNumbers: $versionNumber === null ? [] : [$versionNumber],
            target: $target->structure->publishedVersion,
        );
    }

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
        return $this->target->id === $this->source->id
            ? __('Update to v:number', ['number' => $this->targetVersionNumber()])
            : __('Change template');
    }

    /** @return list<array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => $this->kind->title(), 'url' => $this->kind->listUrl()],
            ['label' => __('Statistics'), 'url' => $this->kind->route('statistics.index')],
            ['label' => $this->title()],
        ];
    }

    /** @return list<string> */
    public function steps(): array
    {
        return [__('Select :name', ['name' => $this->kind->plural()]), __('Preview'), __('Result')];
    }

    public function fromName(): string
    {
        return $this->source->name;
    }

    public function toName(): string
    {
        return __(':template v:number', ['template' => $this->target->name, 'number' => $this->targetVersionNumber()]);
    }

    public function readySentence(): string
    {
        $ready = $this->preview()['counts']['ready'];

        if ($ready === 0) {
            return __('No :name can be moved.', ['name' => $this->kind->singular()]);
        }

        return trans_choice(':count :singular can be moved.|:count :plural can be moved.', $ready, $this->names());
    }

    public function canMove(): bool
    {
        return $this->preview()['counts']['ready'] > 0;
    }

    /** @return list<array{label: string, value: int}> */
    public function effects(): array
    {
        $counts = $this->preview()['counts'];

        return [
            ['label' => __('Records ready to move'), 'value' => $counts['ready']],
            ['label' => __('Records with no field values set aside or restored'), 'value' => $counts['unchanged']],
            ['label' => __('Records with field values set aside'), 'value' => $counts['setAside']],
            ['label' => __('Records with field values restored'), 'value' => $counts['restored']],
            ['label' => __('Reminders paused'), 'value' => $counts['remindersPaused']],
            ['label' => __('Reminders resumed'), 'value' => $counts['remindersResumed']],
        ];
    }

    /** @return list<array{label: string, effect: string}> */
    public function details(): array
    {
        $preview = $this->preview();
        $rows = [];

        foreach ($preview['setAside'] as $field) {
            $rows[] = ['label' => $this->fieldLine($field['label'], $field['records']), 'effect' => __('Set aside')];
        }

        foreach ($preview['restored'] as $field) {
            $rows[] = ['label' => $this->fieldLine($field['label'], $field['records']), 'effect' => __('Restored')];
        }

        foreach ($preview['remindersPaused'] as $reminder) {
            $rows[] = ['label' => $this->reminderLine($reminder['name'], $reminder['records']), 'effect' => __('Paused')];
        }

        foreach ($preview['remindersResumed'] as $reminder) {
            $rows[] = ['label' => $this->reminderLine($reminder['name'], $reminder['records']), 'effect' => __('Resumed')];
        }

        return $rows;
    }

    public function hiddenSentence(): ?string
    {
        $hidden = $this->preview()['hiddenCount'];

        return $hidden === 0 ? null : trans_choice(':count sensitive field is counted without being named.|:count sensitive fields are counted without being named.', $hidden);
    }

    /** @return list<string> */
    public function blocked(): array
    {
        $ids = $this->preview()['blocked'];

        if ($ids === []) {
            return [];
        }

        return $this->batch->kind->recordQuery()
            ->whereIn('id', $ids)
            ->get()
            ->map(fn ($record): string => $record->name)
            ->all();
    }

    public function blockedReason(): string
    {
        return __('Another change of template is already moving these :name.', ['name' => $this->kind->plural()]);
    }

    public function remainingSentence(): string
    {
        return trans_choice(':count :singular remains in this batch.|:count :plural remain in this batch.', $this->preview()['counts']['ready'], $this->names());
    }

    public function moveLabel(): string
    {
        return trans_choice('Move :count :singular|Move :count :plural', $this->preview()['counts']['ready'], $this->names());
    }

    public function createUrl(): string
    {
        return $this->kind->route('templateChanges.create');
    }

    public function backUrl(): string
    {
        return $this->kind->route('templateChanges.new', array_filter([
            'from' => $this->source->uuid,
            'version' => $this->versionNumber,
            'to' => $this->target->uuid,
        ]));
    }

    /** @return array{from: string, version: int|null, to: string} */
    public function parameters(): array
    {
        return ['from' => $this->source->uuid, 'version' => $this->versionNumber, 'to' => $this->target->uuid];
    }

    private function targetVersionNumber(): ?int
    {
        return $this->target->structure->publishedVersion?->version_number;
    }

    private function fieldLine(string $label, int $records): string
    {
        return trans_choice(':field · :count :singular|:field · :count :plural', $records, [...$this->names(), 'field' => $label]);
    }

    private function reminderLine(string $name, int $records): string
    {
        return trans_choice('Reminder “:reminder” · :count :singular|Reminder “:reminder” · :count :plural', $records, [...$this->names(), 'reminder' => $name]);
    }

    /** @return array<string, string> */
    private function names(): array
    {
        return ['singular' => $this->kind->singular(), 'plural' => $this->kind->plural()];
    }

    /** @return array<string, mixed> */
    private function preview(): array
    {
        return $this->preview ??= new BulkTemplateChangePreview(batch: $this->batch, namesSensitiveFields: true)->toArray();
    }
}
