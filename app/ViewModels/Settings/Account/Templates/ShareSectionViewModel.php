<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Helpers\FlagState;
use App\Helpers\StructureFile;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Number;

class ShareSectionViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly StructureVersionSection $row,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function marketplaceIsOn(): bool
    {
        return FlagState::isOn('marketplace', $this->user->account);
    }

    public function template(): Template
    {
        return $this->template;
    }

    public function row(): StructureVersionSection
    {
        return $this->row;
    }

    public function name(): string
    {
        return $this->row->title_override ?? $this->row->sectionVersion->section->name;
    }

    /** @return array<string, mixed> */
    public function file(): array
    {
        return once(fn (): array => StructureFile::ofSection($this->row));
    }

    public function json(): string
    {
        return once(fn (): string => StructureFile::encode($this->file()));
    }

    public function fileName(): string
    {
        return StructureFile::nameOfSection($this->row);
    }

    public function sizeLabel(): string
    {
        return Number::fileSize(strlen($this->json()), precision: 1);
    }

    public function lineLabel(): string
    {
        return trans_choice(
            ':count line · :size|:count lines · :size',
            substr_count($this->json(), "\n") + 1,
            ['size' => $this->sizeLabel()],
        );
    }

    public function structureLabel(): string
    {
        return __(':sections, :fields, :lists', [
            'sections' => __('1 section'),
            'fields' => trans_choice(':count field|:count fields', $this->fieldCount()),
            'lists' => trans_choice(':count value list|:count value lists', $this->valueListCount()),
        ]);
    }

    public function fromLabel(): string
    {
        return __(':name, v:version', [
            'name' => $this->template->name,
            'version' => $this->row->structureVersion->version_number,
        ]);
    }

    /** @return array<int, array{label: string, value: string}> */
    public function inFile(): array
    {
        return [
            ['label' => __('Structure'), 'value' => $this->structureLabel()],
            ['label' => __('Comes from'), 'value' => $this->fromLabel()],
            ['label' => __('Format'), 'value' => __('Monica section format :version', ['version' => StructureFile::FORMAT_VERSION])],
            ['label' => __('Size'), 'value' => $this->sizeLabel()],
        ];
    }

    /** @return array<int, array{label: string, type: string}> */
    public function fields(): array
    {
        return array_map(fn (array $field): array => [
            'label' => $field['label'] ?? __((string) $field['label_translation_key']),
            'type' => FieldCatalogueViewModel::labelFor(FieldTypeEnum::from($field['type'])),
        ], $this->file()['section']['fields']);
    }

    public function fieldCount(): int
    {
        return count($this->file()['section']['fields']);
    }

    public function valueListCount(): int
    {
        return count($this->file()['value_lists']);
    }

    public function shareUrl(): string
    {
        return route('settings.sectionShare.index', [$this->template, $this->row->id]);
    }

    public function exportUrl(): string
    {
        return route('settings.sectionExport.index', [$this->template, $this->row->id]);
    }

    public function downloadUrl(): string
    {
        return route('settings.sectionExport.show', [$this->template, $this->row->id]);
    }

    public function builderUrl(): string
    {
        return route('settings.templateBuilder.index', $this->template);
    }
}
