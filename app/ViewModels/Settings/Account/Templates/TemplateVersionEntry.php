<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use Illuminate\Database\Eloquent\Collection;

class TemplateVersionEntry
{
    public function __construct(
        private readonly TemplateVersion $version,
        private readonly ?TemplateVersion $previous = null,
    ) {}

    public function id(): int
    {
        return $this->version->id;
    }

    public function label(): string
    {
        return __('v:version', ['version' => $this->version->version_number]);
    }

    public function statusLabel(): string
    {
        return match ($this->version->status) {
            VersionStatusEnum::Draft => __('Draft'),
            VersionStatusEnum::Published => __('Published'),
            VersionStatusEnum::Retired => __('Retired'),
        };
    }

    public function tone(): string
    {
        return match ($this->version->status) {
            VersionStatusEnum::Draft => 'warning',
            VersionStatusEnum::Published => 'success',
            VersionStatusEnum::Retired => 'neutral',
        };
    }

    public function isDraft(): bool
    {
        return $this->version->status === VersionStatusEnum::Draft;
    }

    public function dateLine(): string
    {
        $published = $this->version->published_at;

        if ($published !== null) {
            return __('Published :date', ['date' => $published->isoFormat('LL')]);
        }

        return __('Saved :when', ['when' => $this->version->updated_at?->diffForHumans() ?? '']);
    }

    public function basedOn(): string
    {
        if ($this->previous === null) {
            return __('the first version');
        }

        return __('based on v:version', ['version' => $this->previous->version_number]);
    }

    public function changeNote(): string
    {
        $note = $this->version->change_note;

        if ($note !== null && trim($note) !== '') {
            return $note;
        }

        return __('No change note yet. You write one when you publish this version.');
    }

    public function counts(): string
    {
        return implode(' · ', [
            trans_choice(':count page|:count pages', $this->version->pages->count()),
            trans_choice(':count section|:count sections', $this->version->assembly->count()),
            trans_choice(':count field|:count fields', $this->fieldsIn($this->version)),
        ]);
    }

    public function summary(): string
    {
        if ($this->previous === null) {
            return __('Where the template starts');
        }

        $parts = [];

        $pages = $this->version->pages->count() - $this->previous->pages->count();
        $sections = $this->version->assembly->count() - $this->previous->assembly->count();
        $fields = $this->fieldsIn($this->version) - $this->fieldsIn($this->previous);

        if ($pages > 0) {
            $parts[] = trans_choice(':count page added|:count pages added', $pages);
        }

        if ($sections > 0) {
            $parts[] = trans_choice(':count section added|:count sections added', $sections);
        }

        if ($fields > 0) {
            $parts[] = trans_choice(':count field added|:count fields added', $fields);
        }

        if ($sections <= 0 && $this->bumped() > 0) {
            $parts[] = trans_choice(':count section at a new version|:count sections at a new version', $this->bumped());
        }

        if ($parts === []) {
            return __('Nothing changed yet');
        }

        return implode(', ', $parts);
    }

    /** @return array<int, array<string, mixed>> */
    public function pages(): array
    {
        return $this->version->pages
            ->map(fn (TemplateVersionPage $page): array => [
                'label' => $page->label,
                'position' => __('Page :number', ['number' => $page->position]),
                'meta' => $this->metaFor($page),
                'sections' => $this->sectionsOn($page),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionsOn(TemplateVersionPage $page): array
    {
        $carried = $this->previous?->assembly->pluck('section_version_id')->all() ?? [];

        return $this->version->assembly
            ->where('template_version_page_id', $page->id)
            ->map(fn (TemplateVersionSection $row): array => [
                'id' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'icon' => $row->sectionVersion->section->icon,
                'colour' => $row->sectionVersion->section->colour,
                'version' => __('s:version', ['version' => $row->sectionVersion->version_number]),
                'isNew' => $this->previous !== null && ! in_array($row->section_version_id, $carried, true),
                'fieldLabel' => trans_choice(':count field|:count fields', $row->sectionVersion->fields->count()),
                'fields' => $row->sectionVersion->fields
                    ->whereNull('parent_field_id')
                    ->map(fn (Field $field): array => $this->field($field, $row->sectionVersion->fields))
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
    private function field(Field $field, Collection $siblings): array
    {
        return [
            'label' => $field->label,
            'type' => FieldCatalogueViewModel::labelFor($field->type),
            'children' => $siblings
                ->where('parent_field_id', $field->id)
                ->map(fn (Field $child): array => $this->field($child, new Collection))
                ->values()
                ->all(),
        ];
    }

    private function metaFor(TemplateVersionPage $page): string
    {
        $rows = $this->version->assembly->where('template_version_page_id', $page->id);

        return implode(' · ', [
            trans_choice(':count section|:count sections', $rows->count()),
            trans_choice(':count field|:count fields', (int) $rows->sum(fn (TemplateVersionSection $row): int => $row->sectionVersion->fields->count())),
        ]);
    }

    private function bumped(): int
    {
        $carried = $this->previous?->assembly->pluck('section_version_id')->all() ?? [];

        return $this->version->assembly
            ->reject(fn (TemplateVersionSection $row): bool => in_array($row->section_version_id, $carried, true))
            ->count();
    }

    private function fieldsIn(TemplateVersion $version): int
    {
        return (int) $version->assembly->sum(fn (TemplateVersionSection $row): int => $row->sectionVersion->fields->count());
    }
}
