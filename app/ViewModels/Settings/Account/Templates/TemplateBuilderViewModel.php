<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Field;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;

/**
 * What the builder shows: one version of a template, the sections it carries,
 * the fields inside them, and whatever field is open in the inspector.
 *
 * The version it opens is the draft, because that is the only thing anybody can
 * change. A template with nothing but published versions opens read only, with
 * the way forward being to start a draft: this is section 6 made visible rather
 * than a limitation, since a published version is what records are relying on.
 *
 * A section carried here may be published, or may be sitting on somebody else's
 * template at the same time. The screen says so on the section itself, because
 * the first edit will quietly give that section a new version and it is better
 * to have read that beforehand.
 */
class TemplateBuilderViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Template $template,
        private readonly ?int $selectedFieldId = null,
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
     * The sections on this version, in order, each with the fields inside it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function sections(): array
    {
        $version = $this->version();

        if ($version === null) {
            return [];
        }

        return $version->assembly
            ->map(fn (TemplateVersionSection $row): array => [
                'id' => $row->id,
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'isMine' => ! $row->sectionVersion->section->is_system,
                'willBeCopied' => $this->willBeCopied($row),
                'fieldLabel' => self::fieldLabel($row->sectionVersion->fields->count()),
                'fields' => $row->sectionVersion->fields
                    ->map(fn (Field $field): array => [
                        'id' => $field->id,
                        'label' => $field->label,
                        'help' => $field->help_text,
                        'typeLabel' => FieldCatalogueViewModel::labelFor($field->type),
                        'isSensitive' => $field->is_sensitive,
                        'isSelected' => $field->id === $this->selectedFieldId,
                    ])
                    ->all(),
            ])
            ->all();
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
            'sectionName' => $row->title_override ?? $field->sectionVersion->section->name,
        ];
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
