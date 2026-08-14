<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * One version of a template, the sections it carries, the fields inside them,
 * and whatever field is open in the inspector.
 *
 * It opens the draft, since that is the only thing anybody can change; a
 * template with nothing but published versions opens read only. A section
 * carried here may be published or sitting on somebody else's template, and
 * the screen says so on the section itself, because the first edit will
 * quietly give it a new version.
 *
 * The version is read one page at a time, and which page travels in the
 * address beside the field that is open.
 */
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

    /**
     * The version on the screen: the draft if there is one, and otherwise
     * whatever the template offers, so that opening a published template shows
     * what it holds rather than an empty page.
     */
    public function version(): ?TemplateVersion
    {
        return once(fn (): ?TemplateVersion => $this->template->versions->firstWhere('status', VersionStatusEnum::Draft)
            ?? $this->template->publishedVersion
            ?? $this->template->versions->first());
    }

    /**
     * Whether what is on the screen can be changed. Everything the builder
     * offers is hidden behind this, and the model refuses the write underneath
     * it in any case.
     */
    public function isEditable(): bool
    {
        return $this->version()?->status->isEditable() === true;
    }

    /**
     * Whether what is on the screen can leave the account.
     *
     * Only the version the template actually offers can. A draft has nothing
     * frozen to write down, and an older published version is not what the
     * template stands behind, so neither is worth handing anybody a file of.
     *
     * The same answer governs the sections on the screen: publishing a template
     * version refuses to carry a draft section, so every section version pinned
     * by the published one is itself frozen.
     */
    public function isShareable(): bool
    {
        return $this->template->published_version_id !== null
            && $this->version()?->id === $this->template->published_version_id;
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

    /**
     * When the version last changed. Every edit in the builder saves as it is
     * made, so this is what stands in for a save button.
     */
    public function savedLabel(): string
    {
        $version = $this->version();

        if ($version === null) {
            return '';
        }

        return __('Saved :when', ['when' => $version->updated_at?->diffForHumans() ?? '']);
    }

    /**
     * The line at the foot, which says what publishing would and would not do
     * to the records already on the template.
     */
    public function publishingNote(): string
    {
        $published = $this->template->publishedVersion;

        if ($published === null) {
            return __('Nothing is using this template yet. Publishing makes this version the one new records are given.');
        }

        return __('Records already on v:version stay on it until you move them deliberately.', ['version' => $published->version_number]);
    }

    /**
     * The tabs at the top: one per page of the version, in the order they are
     * read in, each counting the fields somebody would find on it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pages(): array
    {
        $version = $this->version();

        if ($version === null) {
            return [];
        }

        $selected = $this->selectedPage();

        return $version->pages
            ->map(fn (TemplateVersionPage $page): array => [
                'id' => $page->id,
                'uuid' => $page->uuid,
                'label' => $page->label,
                'fieldCount' => $this->fieldsOn($page),
                'isSelected' => $page->is($selected),
                'url' => route('settings.templateBuilder.index', ['template' => $this->template, 'page' => $page->key]),
            ])
            ->all();
    }

    /**
     * The page the screen is showing. An address naming a page that is not on
     * this version, or naming none at all, opens the first one rather than
     * refusing: a tab that has gone is not an error worth a page of its own.
     */
    public function selectedPage(): ?TemplateVersionPage
    {
        return once(function (): ?TemplateVersionPage {
            $pages = $this->version()?->pages;

            if ($pages === null || $pages->isEmpty()) {
                return null;
            }

            return $pages->firstWhere('key', $this->selectedPageKey) ?? $pages->first();
        });
    }

    /**
     * Whether the open page can go. Both refusals belong to the action, and
     * asking them here is what lets the screen explain instead of offering
     * something that comes back refused.
     */
    public function canRemovePage(): bool
    {
        $page = $this->selectedPage();

        if ($page === null || ($this->version()?->pages->count() ?? 0) <= 1) {
            return false;
        }

        return $this->version()?->assembly->where('template_version_page_id', $page->id)->isEmpty() === true;
    }

    /**
     * The pages a section on the open one could be moved to.
     *
     * @return array<int, array{id: int, uuid: string, label: string}>
     */
    public function otherPages(): array
    {
        $version = $this->version();
        $page = $this->selectedPage();

        if ($version === null || $page === null) {
            return [];
        }

        return $version->pages
            ->reject(fn (TemplateVersionPage $other): bool => $other->is($page))
            ->map(fn (TemplateVersionPage $other): array => [
                'id' => $other->id,
                'uuid' => $other->uuid,
                'label' => $other->label,
            ])
            ->values()
            ->all();
    }

    /**
     * The sections on the open page, in order, each with the fields inside it.
     * The other pages of the version are not read: what the screen shows is one
     * tab at a time.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sections(): array
    {
        $version = $this->version();
        $page = $this->selectedPage();

        if ($version === null || $page === null) {
            return [];
        }

        return $version->assembly
            ->where('template_version_page_id', $page->id)
            ->map(fn (TemplateVersionSection $row): array => [
                'id' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
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
     * One field on the list, with whatever sits inside it when it is a repeater.
     * A repeater cannot hold another, so this goes one level down and stops.
     *
     * @param  Collection<int, Field>  $siblings
     * @return array<string, mixed>
     */
    private function field(Field $field, TemplateVersionPage $page, Collection $siblings): array
    {
        return [
            'id' => $field->id,
            'label' => $field->label,
            'help' => $field->help_text,
            'typeLabel' => FieldCatalogueViewModel::labelFor($field->type),
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

    /**
     * The field open in the inspector, if the one asked for belongs to this
     * template at all.
     *
     * @return array<string, mixed>|null
     */
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
            'sectionName' => $this->pathTo($row),
        ];
    }

    /**
     * Where the open field sits, said as somebody would say it. The page is
     * named only when it is not the one on the screen, since repeating the tab
     * they are looking at tells them nothing.
     */
    private function pathTo(?TemplateVersionSection $row): string
    {
        if ($row === null) {
            return '';
        }

        $name = $row->title_override ?? $row->sectionVersion->section->name;
        $page = $this->version()?->pages->firstWhere('id', $row->template_version_page_id);

        if ($page === null || $page->is($this->selectedPage())) {
            return $name;
        }

        return __(':page › :section', ['page' => $page->label, 'section' => $name]);
    }

    private function fieldsOn(TemplateVersionPage $page): int
    {
        return (int) ($this->version()?->assembly
            ->where('template_version_page_id', $page->id)
            ->sum(fn (TemplateVersionSection $row): int => $row->sectionVersion->fields->count()) ?? 0);
    }

    /**
     * What deleting the open field would cost. Nothing has ever been written in
     * a field yet, since records are a later model, and saying so plainly beats
     * a count that is always nought.
     */
    public function usageNote(): string
    {
        return __('Nothing has been written in this field yet, so deleting it removes nothing.');
    }

    public function selectedFieldId(): ?int
    {
        return $this->findSelectedField()?->id;
    }

    /**
     * Whether editing anything in this section will give the section a new
     * version first: it will, unless the version here is a draft this template
     * is alone in carrying.
     */
    private function willBeCopied(TemplateVersionSection $row): bool
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
            ->with('sectionVersion.section')
            ->first());
    }

    private static function fieldLabel(int $count): string
    {
        if ($count === 0) {
            return __('no fields');
        }

        return $count === 1 ? __('1 field') : __(':count fields', ['count' => $count]);
    }
}
