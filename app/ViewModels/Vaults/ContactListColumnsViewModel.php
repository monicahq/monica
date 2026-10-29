<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ContactListColumnSourceEnum;
use App\Helpers\ContactListCatalogue;
use App\Models\ContactListColumn;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Str;

class ContactListColumnsViewModel
{
    private ?ContactListCatalogue $catalogue = null;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Customise contact list');
    }

    /**
     * The columns after the name, as the vault shows them now, for the editor to start from.
     *
     * @return list<array{id: string, source: string, section: string|null, fieldKey: string|null, heading: string, defaultHeading: string, sourceLabel: string, note: string, isVisible: bool, isSupplied: bool}>
     */
    public function columns(): array
    {
        $submitted = old('columns');

        $columns = is_array($submitted)
            ? $this->columnsSubmitted($submitted)
            : $this->catalogue()->columnsOf($this->vault);

        return array_map(fn (ContactListColumn $column): array => $this->describe($column), $columns);
    }

    /**
     * What Reset to defaults puts back.
     *
     * @return list<array{id: string, source: string, section: string|null, fieldKey: string|null, heading: string, defaultHeading: string, sourceLabel: string, note: string, isVisible: bool, isSupplied: bool}>
     */
    public function defaultColumns(): array
    {
        return array_map(fn (array $default): array => $this->describe(new ContactListColumn([
            'source' => $default['source'],
            'is_visible' => $default['isVisible'],
        ])), ContactListColumnSourceEnum::defaults());
    }

    /**
     * What may be added, in two runs: what every template lets any contact show, then what only
     * some templates carry, with the templates that do.
     *
     * @return list<array{label: string, help: string, items: list<array{column: array{id: string, source: string, section: string|null, fieldKey: string|null, heading: string, defaultHeading: string, sourceLabel: string, note: string, isVisible: bool, isSupplied: bool}, label: string, templates: list<string>}>}>
     */
    public function additions(): array
    {
        $shared = [];
        $some = [];

        foreach (ContactListColumnSourceEnum::shared() as $source) {
            $shared[] = [
                'column' => $this->describe(new ContactListColumn(['source' => $source, 'is_visible' => true])),
                'label' => __($source->label()),
                'templates' => [],
            ];
        }

        foreach ($this->catalogue()->fields() as $field) {
            $item = [
                'column' => $this->describe(new ContactListColumn([
                    'source' => ContactListColumnSourceEnum::Field,
                    'section_id' => $field['sectionId'],
                    'field_key' => $field['fieldKey'],
                    'is_visible' => true,
                ])),
                'label' => $field['label'],
                'templates' => $field['templates'],
            ];

            if ($this->catalogue()->isCarriedByEveryTemplate($field)) {
                $shared[] = [...$item, 'templates' => []];

                continue;
            }

            $some[] = $item;
        }

        $groups = [[
            'label' => __('Shared by all templates'),
            'help' => __('Every template has these, so any contact can show a value.'),
            'items' => $shared,
        ]];

        if ($some !== []) {
            $groups[] = [
                'label' => __('Unique to some templates'),
                'help' => __('Contacts using other templates show a dash.'),
                'items' => $some,
            ];
        }

        return $groups;
    }

    public function templatesLine(): string
    {
        $names = $this->catalogue()->templateNames();

        if ($names === []) {
            return __('No contact template is published yet, so only the shared columns can be added.');
        }

        return __('Every field from the templates your contacts use: :templates.', ['templates' => implode(__(', '), $names)]);
    }

    public function formUrl(): string
    {
        return route('contactListColumns.update', $this->vault);
    }

    public function cancelUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /**
     * What somebody sent before the form came back refused, so that their changes are not lost.
     *
     * @param  array<array-key, mixed>  $submitted
     * @return list<ContactListColumn>
     */
    private function columnsSubmitted(array $submitted): array
    {
        $sections = Section::query()
            ->where('account_id', $this->user->account_id)
            ->whereIn('uuid', array_filter(array_map(fn (mixed $column): mixed => is_array($column) ? ($column['section'] ?? null) : null, $submitted), fn (mixed $section): bool => is_string($section) && Str::isUuid($section)))
            ->get()
            ->keyBy('uuid');

        $columns = [];

        foreach ($submitted as $column) {
            if (! is_array($column)) {
                continue;
            }

            $source = ContactListColumnSourceEnum::tryFrom((string) ($column['source'] ?? ''));
            $section = $sections->get((string) ($column['section'] ?? ''));

            if ($source === null || ($source === ContactListColumnSourceEnum::Field && $section === null)) {
                continue;
            }

            $restored = new ContactListColumn([
                'source' => $source,
                'section_id' => $section?->id,
                'field_key' => $source === ContactListColumnSourceEnum::Field ? (string) ($column['field_key'] ?? '') : null,
                'heading' => ($column['heading'] ?? '') === '' ? null : (string) $column['heading'],
                'is_visible' => (bool) ($column['is_visible'] ?? false),
            ]);
            $restored->setRelation('section', $section);
            $columns[] = $restored;
        }

        return $columns;
    }

    /** @return array{id: string, source: string, section: string|null, fieldKey: string|null, heading: string, defaultHeading: string, sourceLabel: string, note: string, isVisible: bool, isSupplied: bool} */
    private function describe(ContactListColumn $column): array
    {
        $field = $column->source === ContactListColumnSourceEnum::Field
            ? $this->catalogue()->find((int) $column->section_id, (string) $column->field_key)
            : null;

        $isSupplied = $this->catalogue()->isSupplied($column);
        $note = match (true) {
            ! $isSupplied => __('No template supplies this any more, so the list leaves it out.'),
            $field !== null => $this->catalogue()->isCarriedByEveryTemplate($field)
                ? __('Every template carries it.')
                : __('Only contacts using :templates show a value.', ['templates' => implode(__(', '), $field['templates'])]),
            default => $this->noteFor($column),
        };
        $sectionUuid = $field['sectionUuid'] ?? $column->section?->uuid;

        return [
            'id' => "{$column->source->value}:{$sectionUuid}:{$column->field_key}",
            'source' => $column->source->value,
            'section' => $sectionUuid,
            'fieldKey' => $column->field_key,
            'heading' => (string) $column->heading,
            'defaultHeading' => $this->catalogue()->defaultHeadingOf($column),
            'sourceLabel' => $field === null
                ? $this->catalogue()->defaultHeadingOf($column)
                : __(':section → :field', ['section' => $field['sectionName'], 'field' => $field['label']]),
            'note' => $note,
            'isVisible' => $column->is_visible,
            'isSupplied' => $isSupplied,
        ];
    }

    private function noteFor(ContactListColumn $column): string
    {
        return match ($column->source) {
            ContactListColumnSourceEnum::Nickname => __('The name they go by, if you wrote one down.'),
            ContactListColumnSourceEnum::Email => __('Collects every email field on the contact, whatever its template calls it.'),
            ContactListColumnSourceEnum::Phone => __('Collects every phone field on the contact, whatever its template calls it.'),
            ContactListColumnSourceEnum::Birthday => __('The birthday, in whichever section holds it.'),
            ContactListColumnSourceEnum::Template => __('Which template the contact uses.'),
            ContactListColumnSourceEnum::LastSpoke => __('Shows “Not yet” when no interaction is recorded.'),
            ContactListColumnSourceEnum::Added => __('When the contact was written down.'),
            ContactListColumnSourceEnum::Field => '',
        };
    }

    private function catalogue(): ContactListCatalogue
    {
        return $this->catalogue ??= new ContactListCatalogue($this->vault->account);
    }
}
