<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\CreateTemplateChange;
use App\Helpers\RecordKind;
use App\Helpers\TemplateChangeBatch;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;

class TemplateChangeFormViewModel
{
    private ?TemplateChangeBatch $batch = null;

    public function __construct(
        private readonly User $user,
        private readonly RecordKind $kind,
        private readonly Template $source,
        private readonly ?int $versionNumber = null,
        private readonly ?Template $target = null,
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

    public function isUpdate(): bool
    {
        return $this->target?->id === $this->source->id;
    }

    public function title(): string
    {
        if ($this->isUpdate()) {
            return __('Update to v:number', ['number' => $this->source->structure->publishedVersion?->version_number]);
        }

        return __('Change template');
    }

    public function context(): string
    {
        $count = $this->count();

        if ($this->isUpdate()) {
            return trans_choice(':template · :count :singular on older versions · :vault vault|:template · :count :plural on older versions · :vault vault', $count, $this->names());
        }

        return trans_choice(':template · :count :singular · :vault vault|:template · :count :plural · :vault vault', $count, $this->names());
    }

    public function count(): int
    {
        return $this->batch()->movable()->count();
    }

    public function countLabel(): string
    {
        return trans_choice(':count :singular|:count :plural', $this->count(), $this->names());
    }

    public function batchDescription(): string
    {
        if ($this->versionNumber !== null) {
            return __('Every non-archived :name using :template v:number in this vault', ['name' => $this->kind->singular(), 'template' => $this->source->name, 'number' => $this->versionNumber]);
        }

        if ($this->isUpdate()) {
            return __('Every non-archived :name using an older version of :template in this vault', ['name' => $this->kind->singular(), 'template' => $this->source->name]);
        }

        return __('Every non-archived :name using :template in this vault', ['name' => $this->kind->singular(), 'template' => $this->source->name]);
    }

    /** @return list<array{label: string, url: string, selected: bool}> */
    public function versionChoices(): array
    {
        $published = $this->source->structure->published_version_id;
        $choices = [[
            'label' => __('All versions'),
            'url' => $this->url(version: null),
            'selected' => $this->versionNumber === null,
        ]];

        $versions = StructureVersion::query()
            ->whereIn('id', $this->kind->recordQuery()->where('is_archived', false)->select('structure_version_id'))
            ->where('structure_id', $this->source->structure->id)
            ->when($this->isUpdate(), fn ($query) => $query->whereKeyNot($published))
            ->orderByDesc('version_number')
            ->pluck('version_number');

        foreach ($versions as $number) {
            $choices[] = [
                'label' => __('v:number', ['number' => $number]),
                'url' => $this->url(version: $number),
                'selected' => $this->versionNumber === $number,
            ];
        }

        return $choices;
    }

    public function viewRecordsUrl(): string
    {
        return $this->kind->listUrl(array_filter(['template' => $this->source->uuid, 'version' => $this->versionNumber]));
    }

    public function viewRecordsLabel(): string
    {
        return trans_choice('View :count :singular|View :count :plural', $this->count(), $this->names());
    }

    /** @return list<array{value: string, label: string, isDefault: bool, checked: bool}> */
    public function destinations(): array
    {
        $defaultTemplateId = $this->kind->defaultTemplateId();

        $destinations = $this->kind->templates()
            ->filter(fn (Template $template): bool => CreateTemplateChange::refusal($this->kind, $this->source, $template) === null)
            ->reject(fn (Template $template): bool => ! $this->isUpdate() && $template->id === $this->source->id)
            ->reject(fn (Template $template): bool => $this->isUpdate() && $template->id !== $this->source->id)
            ->map(fn (Template $template): array => [
                'value' => $template->uuid,
                'label' => __(':template · v:number', ['template' => $template->name, 'number' => $template->structure->publishedVersion->version_number]),
                'isDefault' => $template->id === $defaultTemplateId,
                'checked' => $this->target?->id === $template->id,
            ])
            ->values()
            ->all();

        if (array_filter(array_column($destinations, 'checked')) !== []) {
            return $destinations;
        }

        foreach ($destinations as $index => $destination) {
            $destinations[$index]['checked'] = $destination['isDefault'] || count($destinations) === 1;
        }

        return $destinations;
    }

    public function sourceId(): string
    {
        return $this->source->uuid;
    }

    public function versionNumber(): ?int
    {
        return $this->versionNumber;
    }

    public function previewUrl(): string
    {
        return $this->kind->route('templateChanges.preview');
    }

    public function backUrl(): string
    {
        return $this->kind->route('statistics.index');
    }

    private function url(?int $version): string
    {
        return $this->kind->route('templateChanges.new', array_filter([
            'from' => $this->source->uuid,
            'version' => $version,
            'to' => $this->target?->uuid,
        ]));
    }

    /** @return array<string, string> */
    private function names(): array
    {
        return [
            'template' => $this->source->name,
            'singular' => $this->kind->singular(),
            'plural' => $this->kind->plural(),
            'vault' => $this->kind->vault->name,
        ];
    }

    private function batch(): TemplateChangeBatch
    {
        return $this->batch ??= new TemplateChangeBatch(
            kind: $this->kind,
            source: $this->source,
            versionNumbers: $this->versionNumber === null ? [] : [$this->versionNumber],
            target: $this->isUpdate() ? $this->source->structure->publishedVersion : null,
        );
    }
}
