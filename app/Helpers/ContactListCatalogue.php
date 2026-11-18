<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\ContactListColumnSourceEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\StructureAppliesToEnum;
use App\Models\Account;
use App\Models\ContactListColumn;
use App\Models\Field;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * What the list of people may show and does show. The fields it may show are every field the
 * published version of an active contact template carries, named by its section and its key, with
 * the templates carrying it. The columns it shows are the ones a vault saved, or the defaults.
 */
class ContactListCatalogue
{
    /**
     * What a line of text in a cell can show. Email, phone and the birthday have shared columns of
     * their own, so they are left out here rather than offered twice.
     */
    private const array SHOWN_TYPES = [
        FieldTypeEnum::Text,
        FieldTypeEnum::Textarea,
        FieldTypeEnum::Number,
        FieldTypeEnum::Duration,
        FieldTypeEnum::Boolean,
        FieldTypeEnum::Url,
        FieldTypeEnum::Select,
        FieldTypeEnum::Multiselect,
        FieldTypeEnum::Radio,
        FieldTypeEnum::Date,
        FieldTypeEnum::RecurringDate,
        FieldTypeEnum::Address,
    ];

    private const string BIRTHDAY_KEY = 'birthday';

    /** @var Collection<int, Template>|null */
    private ?Collection $templates = null;

    /** @var array<string, array{sectionId: int, sectionUuid: string, sectionName: string, fieldKey: string, label: string, templates: list<string>, templateIds: list<int>}>|null */
    private ?array $fields = null;

    public function __construct(
        private readonly Account $account,
    ) {}

    /**
     * The columns a vault holds, or the defaults when it saved none, hidden ones included.
     *
     * @return list<ContactListColumn>
     */
    public function columnsOf(Vault $vault): array
    {
        $saved = $vault->contactListColumns()->with('section')->get()->all();

        if ($saved !== []) {
            return $saved;
        }

        return array_map(fn (array $default, int $index): ContactListColumn => new ContactListColumn([
            'source' => $default['source'],
            'is_visible' => $default['isVisible'],
            'position' => $index + 1,
        ]), ContactListColumnSourceEnum::defaults(), array_keys(ContactListColumnSourceEnum::defaults()));
    }

    /**
     * The columns the list shows: less those hidden, and those no template supplies any more.
     *
     * @return list<ContactListColumn>
     */
    public function shownColumnsOf(Vault $vault): array
    {
        return array_values(array_filter(
            $this->columnsOf($vault),
            fn (ContactListColumn $column): bool => $column->is_visible && $this->isSupplied($column),
        ));
    }

    public function isSupplied(ContactListColumn $column): bool
    {
        return $column->source !== ContactListColumnSourceEnum::Field
            || $this->supplies((int) $column->section_id, (string) $column->field_key);
    }

    /**
     * What a column is called when nobody renamed it, in the reader's language when the product
     * supplied the words. A field no template carries any more keeps the key it was saved with.
     */
    public function defaultHeadingOf(ContactListColumn $column): string
    {
        if ($column->source !== ContactListColumnSourceEnum::Field) {
            return __($column->source->label());
        }

        return $this->find((int) $column->section_id, (string) $column->field_key)['label'] ?? (string) $column->field_key;
    }

    public function headingOf(ContactListColumn $column): string
    {
        return $column->heading ?? $this->defaultHeadingOf($column);
    }

    /** @return list<string> */
    public function templateNames(): array
    {
        return $this->templates()->map(fn (Template $template): string => $template->name)->values()->all();
    }

    /** @return list<array{sectionId: int, sectionUuid: string, sectionName: string, fieldKey: string, label: string, templates: list<string>, templateIds: list<int>}> */
    public function fields(): array
    {
        return array_values($this->catalogue());
    }

    /** @return array{sectionId: int, sectionUuid: string, sectionName: string, fieldKey: string, label: string, templates: list<string>, templateIds: list<int>}|null */
    public function find(int $sectionId, string $fieldKey): ?array
    {
        return $this->catalogue()["{$sectionId}:{$fieldKey}"] ?? null;
    }

    /** @param  array{templateIds: list<int>}  $field */
    public function isCarriedByEveryTemplate(array $field): bool
    {
        return count($field['templateIds']) === $this->templates()->count();
    }

    public function supplies(int $sectionId, string $fieldKey): bool
    {
        return $this->find($sectionId, $fieldKey) !== null;
    }

    /** @return array<string, array{sectionId: int, sectionUuid: string, sectionName: string, fieldKey: string, label: string, templates: list<string>, templateIds: list<int>}> */
    private function catalogue(): array
    {
        if ($this->fields !== null) {
            return $this->fields;
        }

        $this->fields = [];

        foreach ($this->templates() as $template) {
            foreach ($template->structure->publishedVersion->sectionVersions ?? [] as $sectionVersion) {
                $this->collect($template, $sectionVersion);
            }
        }

        return $this->fields;
    }

    private function collect(Template $template, SectionVersion $sectionVersion): void
    {
        $section = $sectionVersion->section;

        if ($section === null) {
            return;
        }

        foreach ($sectionVersion->fields as $field) {
            if (! $this->isShown($field)) {
                continue;
            }

            $key = "{$section->id}:{$field->key}";

            $this->fields[$key] ??= [
                'sectionId' => $section->id,
                'sectionUuid' => $section->uuid,
                'sectionName' => $section->name,
                'fieldKey' => $field->key,
                'label' => $field->label,
                'templates' => [],
                'templateIds' => [],
            ];

            if (! in_array($template->id, $this->fields[$key]['templateIds'], true)) {
                $this->fields[$key]['templates'][] = $template->name;
                $this->fields[$key]['templateIds'][] = $template->id;
            }
        }
    }

    private function isShown(Field $field): bool
    {
        return in_array($field->type, self::SHOWN_TYPES, true)
            && ! $field->is_sensitive
            && $field->key !== self::BIRTHDAY_KEY;
    }

    /** @return Collection<int, Template> */
    private function templates(): Collection
    {
        return $this->templates ??= Template::query()
            ->where('account_id', $this->account->id)
            ->where('is_active', true)
            ->whereHas('structure', function (Builder $query): void {
                $query->where('applies_to', StructureAppliesToEnum::Contact)
                    ->whereNotNull('published_version_id');
            })
            ->with('structure.publishedVersion.sectionVersions.fields', 'structure.publishedVersion.sectionVersions.section')
            ->orderBy('position')
            ->get();
    }
}
