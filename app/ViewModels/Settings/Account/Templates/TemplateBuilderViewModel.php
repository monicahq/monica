<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\RepetitionOrderEnum;
use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Section;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as Support;
use Illuminate\Support\Facades\DB;

class TemplateBuilderViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly ?int $selectedFieldId = null,
        private readonly ?string $selectedPageKey = null,
    ) {}

    public function template(): Template
    {
        return $this->template;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function version(): ?StructureVersion
    {
        return once(fn (): ?StructureVersion => $this->template->versions->firstWhere('status', VersionStatusEnum::Draft)
            ?? $this->template->structure->publishedVersion
            ?? $this->template->versions->first());
    }

    public function isEditable(): bool
    {
        return $this->version()?->status->isEditable() === true;
    }

    public function hasHistory(): bool
    {
        return $this->template->versions->count() > 1;
    }

    public function isShareable(): bool
    {
        return $this->template->structure->published_version_id !== null
            && $this->version()?->id === $this->template->structure->published_version_id;
    }

    public function versionLabel(): string
    {
        $version = $this->version();

        if ($version === null) {
            return __('no version yet');
        }

        $previous = $this->template->versions->firstWhere('id', $version->previous_version_id);

        return $previous === null
            ? __('v:version', ['version' => $version->version_number])
            : __('v:version · based on v:previous', ['version' => $version->version_number, 'previous' => $previous->version_number]);
    }

    public function statusLabel(): string
    {
        return match ($this->version()?->status) {
            VersionStatusEnum::Draft => __('Draft'),
            VersionStatusEnum::Published => __('Published'),
            VersionStatusEnum::Retired => __('Retired'),
            default => __('Empty'),
        };
    }

    public function savedLabel(): string
    {
        $version = $this->version();

        if ($version === null) {
            return '';
        }

        return __('Saved :when', ['when' => $version->updated_at?->diffForHumans() ?? '']);
    }

    public function publishingNote(): string
    {
        $published = $this->template->structure->publishedVersion;

        if ($published === null) {
            return __('Nothing is using this template yet. Publishing makes this version the one new records are given.');
        }

        return __('Records already on v:version stay on it until you move them deliberately.', ['version' => $published->version_number]);
    }

    /** @return array<int, array<string, mixed>> */
    public function pages(): array
    {
        $version = $this->version();

        if ($version === null) {
            return [];
        }

        $selected = $this->selectedPage();

        return $version->pages
            ->map(fn (StructureVersionPage $page): array => [
                'id' => $page->id,
                'uuid' => $page->uuid,
                'label' => $page->label,
                'fieldCount' => $this->fieldsOn($page),
                'isSelected' => $page->is($selected),
                'url' => route('settings.templateBuilder.index', ['template' => $this->template, 'page' => $page->key]),
            ])
            ->all();
    }

    public function selectedPage(): ?StructureVersionPage
    {
        return once(function (): ?StructureVersionPage {
            $pages = $this->version()?->pages;

            if ($pages === null || $pages->isEmpty()) {
                return null;
            }

            return $pages->firstWhere('key', $this->selectedPageKey) ?? $pages->first();
        });
    }

    public function canRemovePage(): bool
    {
        $page = $this->selectedPage();

        if ($page === null || ($this->version()?->pages->count() ?? 0) <= 1) {
            return false;
        }

        return $this->version()?->assembly->where('structure_version_page_id', $page->id)->isEmpty() === true;
    }

    /** @return array<int, array{id: int, uuid: string, label: string}> */
    public function otherPages(): array
    {
        $version = $this->version();
        $page = $this->selectedPage();

        if ($version === null || $page === null) {
            return [];
        }

        return $version->pages
            ->reject(fn (StructureVersionPage $other): bool => $other->is($page))
            ->map(fn (StructureVersionPage $other): array => [
                'id' => $other->id,
                'uuid' => $other->uuid,
                'label' => $other->label,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    private function otherTemplateCounts(): array
    {
        return once(function (): array {
            $version = $this->version();
            $page = $this->selectedPage();

            if ($version === null || $page === null) {
                return [];
            }

            $sectionIds = $version->assembly
                ->where('structure_version_page_id', $page->id)
                ->map(fn (StructureVersionSection $row): int => $row->sectionVersion->section_id)
                ->unique()
                ->all();

            if ($sectionIds === []) {
                return [];
            }

            return DB::table('structure_version_sections')
                ->join('section_versions', 'section_versions.id', '=', 'structure_version_sections.section_version_id')
                ->join('structure_versions', 'structure_versions.id', '=', 'structure_version_sections.structure_version_id')
                ->whereIn('section_versions.section_id', $sectionIds)
                ->where('structure_versions.structure_id', '!=', $this->template->structure->id)
                ->distinct()
                ->get(['section_versions.section_id', 'structure_versions.structure_id'])
                ->groupBy('section_id')
                ->map(fn (Support $rows): int => $rows->pluck('structure_id')->unique()->count())
                ->all();
        });
    }

    private function markReach(StructureVersionSection $row): string
    {
        $others = $this->otherTemplateCounts()[$row->sectionVersion->section_id] ?? 0;

        if ($others === 0) {
            return __('No other template carries this section, so the mark stays here.');
        }

        return trans_choice(
            'The mark also changes on :count other template carrying this section.|The mark also changes on :count other templates carrying this section.',
            $others,
        );
    }

    /** @return list<string> */
    public function glyphs(): array
    {
        return Section::ICONS;
    }

    /** @return array<int, array{value: string|null, label: string, tint: string, swatch: string|null}> */
    public function colours(): array
    {
        $neutral = [[
            'value' => null,
            'label' => __('Neutral'),
            'tint' => VaultColourEnum::neutralTint(),
            'swatch' => null,
        ]];

        return array_merge($neutral, array_map(
            fn (VaultColourEnum $colour): array => [
                'value' => $colour->value,
                'label' => __($colour->label()),
                'tint' => $colour->tint(),
                'swatch' => $colour->value,
            ],
            VaultColourEnum::cases(),
        ));
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        $version = $this->version();
        $page = $this->selectedPage();

        if ($version === null || $page === null) {
            return [];
        }

        return $version->assembly
            ->where('structure_version_page_id', $page->id)
            ->map(fn (StructureVersionSection $row): array => [
                'id' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'icon' => $row->sectionVersion->section->icon,
                'colour' => $row->sectionVersion->section->colour,
                'markReach' => $this->markReach($row),
                'isMine' => ! $row->sectionVersion->section->is_system,
                'willBeCopied' => $this->willBeCopied($row),
                'fieldLabel' => self::fieldLabel($row->sectionVersion->fields->count()),
                'fields' => $row->sectionVersion->fields
                    ->whereNull('parent_field_id')
                    ->map(fn (Field $field): array => $this->field($field, $page, $row->sectionVersion->fields))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Field>  $siblings
     * @return array<string, mixed>
     */
    private function field(Field $field, StructureVersionPage $page, Collection $siblings): array
    {
        return [
            'id' => $field->id,
            'label' => $field->label,
            'help' => $field->help_text,
            'typeLabel' => FieldCatalogueViewModel::labelFor($field->type),
            'repetitionLabel' => $field->repetition_order?->addsToTheFront() === true
                ? $field->repetition_order->label()
                : null,
            'isSensitive' => $field->is_sensitive,
            'isSelected' => $field->id === $this->selectedFieldId,
            'url' => route('settings.templateBuilder.field', [
                'template' => $this->template,
                'page' => $page->key,
                'field' => $field->id,
            ]),
            'children' => $siblings
                ->where('parent_field_id', $field->id)
                ->map(fn (Field $child): array => $this->field($child, $page, new Collection))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    public function selectedField(): ?array
    {
        $field = $this->findSelectedField();

        if ($field === null) {
            return null;
        }

        $row = $this->version()?->assembly->firstWhere('section_version_id', $field->section_version_id);

        return [
            'id' => $field->id,
            'rowId' => $row?->id,
            'key' => $field->key,
            'label' => $field->label,
            'help' => $field->help_text,
            'placeholder' => $field->placeholder,
            'typeLabel' => FieldCatalogueViewModel::labelFor($field->type),
            'isRequired' => $field->is_required,
            'isSensitive' => $field->is_sensitive,
            'isHighlighted' => $field->is_highlighted,
            'isRepeater' => $field->type->isRepeater(),
            'repetitionOrder' => ($field->repetition_order ?? RepetitionOrderEnum::NewestLast)->value,
            'sectionName' => $this->pathTo($row),
            'isRelation' => $field->type->isRelation(),
            'relationTargetLabel' => $field->relationEntityType->name_plural ?? __('Somebody in the vault'),
            'relationTargetIsInactive' => $field->relationEntityType !== null && ! $field->relationEntityType->is_active,
            'holdsAFile' => $field->type->holdsAFile(),
            'acceptedMimeTypes' => implode(', ', $field->acceptedMimeTypes()),
            'addInsideUrl' => $field->type->isRepeater() && $this->isEditable() && $row !== null
                ? route('settings.childFields.new', [$this->template, $row, $field])
                : null,
        ];
    }

    private function pathTo(?StructureVersionSection $row): string
    {
        if ($row === null) {
            return '';
        }

        $name = $row->title_override ?? $row->sectionVersion->section->name;
        $page = $this->version()?->pages->firstWhere('id', $row->structure_version_page_id);

        if ($page === null || $page->is($this->selectedPage())) {
            return $name;
        }

        return __(':page › :section', ['page' => $page->label, 'section' => $name]);
    }

    private function fieldsOn(StructureVersionPage $page): int
    {
        return (int) ($this->version()?->assembly
            ->where('structure_version_page_id', $page->id)
            ->sum(fn (StructureVersionSection $row): int => $row->sectionVersion->fields->count()) ?? 0);
    }

    public function usageNote(): string
    {
        return __('Nothing has been written in this field yet, so deleting it removes nothing.');
    }

    public function selectedFieldId(): ?int
    {
        return $this->findSelectedField()?->id;
    }

    private function willBeCopied(StructureVersionSection $row): bool
    {
        $version = $row->sectionVersion;

        return ! ($version->status->isEditable() && $version->usage_count <= 1);
    }

    private function findSelectedField(): ?Field
    {
        if ($this->selectedFieldId === null) {
            return null;
        }

        return once(fn (): ?Field => Field::query()
            ->whereKey($this->selectedFieldId)
            ->where('account_id', $this->user->account_id)
            ->whereIn('section_version_id', $this->version()?->assembly->pluck('section_version_id') ?? [])
            ->with(['sectionVersion.section', 'relationEntityType'])
            ->first());
    }

    private static function fieldLabel(int $count): string
    {
        if ($count === 0) {
            return __('no fields');
        }

        return trans_choice(':count field|:count fields', $count);
    }
}
