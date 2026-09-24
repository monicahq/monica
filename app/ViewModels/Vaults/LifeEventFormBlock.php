<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

class LifeEventFormBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?LifeEvent $lifeEvent = null,
    ) {}

    public function isEditing(): bool
    {
        return $this->lifeEvent instanceof LifeEvent;
    }

    public function editingId(): ?int
    {
        return $this->lifeEvent?->id;
    }

    public function title(): string
    {
        return $this->isEditing() ? __('Edit life event') : __('Add a life event');
    }

    /** @return array{pick: string, details: string} */
    public function captions(): array
    {
        return $this->isEditing()
            ? ['pick' => __('Choose a different type'), 'details' => __('Change anything, then save')]
            : ['pick' => __('Step 1 of 2 · Choose what happened'), 'details' => __('Step 2 of 2 · Fill in the details')];
    }

    public function submitLabel(): string
    {
        return $this->isEditing() ? __('Save changes') : __('Add to the timeline');
    }

    public function method(): string
    {
        return $this->isEditing() ? 'put' : 'post';
    }

    public function action(): string
    {
        if ($this->lifeEvent instanceof LifeEvent) {
            return route('contacts.lifeEvents.update', ['vault' => $this->vault, 'contact' => $this->contact, 'lifeEvent' => $this->lifeEvent]);
        }

        return $this->createUrl();
    }

    public function cancelUrl(): ?string
    {
        return $this->isEditing() ? route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]) : null;
    }

    /**
     * What one of the pieces every life event carries holds: what was typed a
     * moment ago when the form came back refused, else what is stored.
     */
    public function pieceValue(string $piece): ?string
    {
        $stored = $this->lifeEvent?->getAttribute($piece);

        $value = old($piece, $stored === null ? null : (string) $stored);

        return $value === null ? null : (string) $value;
    }

    /** @return array<int, array{id: int, name: string, typeCount: int, types: array<int, array{id: int, name: string, categoryName: string, icon: string, fieldCountLabel: string, fieldsUrl: string}>}> */
    public function categories(): array
    {
        return once(fn (): array => LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', true)
            ->with(['lifeEventTypes' => fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('position')
                ->with('structure.publishedVersion.assembly')])
            ->orderBy('position')
            ->get()
            ->map(fn (LifeEventCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'typeCount' => $category->lifeEventTypes->count(),
                'types' => $category->lifeEventTypes
                    ->map(fn (LifeEventType $type): array => $this->typeRow($type, $category))
                    ->all(),
            ])
            ->all());
    }

    public function typeCount(): int
    {
        return array_sum(array_column($this->categories(), 'typeCount'));
    }

    /**
     * The heading over the search results, worded for every number of results
     * the search can find. The reader filters the rendered types without a
     * request, so the wording for each count is written here, where the rules
     * of the reader's language are known.
     *
     * @return array<int, string>
     */
    public function resultHeadings(): array
    {
        $headings = [];

        for ($count = 1; $count <= $this->typeCount(); $count++) {
            $headings[$count] = trans_choice(':count type|:count types', $count);
        }

        return $headings;
    }

    public function createUrl(): string
    {
        return route('contacts.lifeEvents.create', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function candidatesUrl(): string
    {
        return route('contacts.lifeEventCandidates.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function settingsUrl(): string
    {
        return route('settings.lifeEventTypes.index');
    }

    public function today(): string
    {
        return now($this->user->timezone)->toDateString();
    }

    public function contactName(): string
    {
        return $this->contact->first_name ?? $this->contact->name;
    }

    /**
     * The type the reader had chosen when the form came back refused, so the
     * panel opens where they were rather than at the start.
     */
    public function oldType(): ?LifeEventType
    {
        return once(function (): ?LifeEventType {
            $id = old('life_event_type_id');

            if (! is_numeric($id)) {
                return $this->lifeEvent?->lifeEventType->load('lifeEventCategory');
            }

            return LifeEventType::query()
                ->where('account_id', $this->user->account_id)
                ->with('lifeEventCategory')
                ->find((int) $id);
        });
    }

    /** @return array<int, array{id: int, name: string}> */
    public function oldParticipants(): array
    {
        $ids = old('life_event_type_id') === null && $this->lifeEvent instanceof LifeEvent
            ? $this->lifeEvent->participants()->where('participant_type', Contact::class)->pluck('participant_id')->all()
            : array_filter((array) old('participants', []), is_numeric(...));

        if ($ids === []) {
            return [];
        }

        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->whereKeyNot($this->contact->id)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Contact $contact): int|false => array_search($contact->id, array_values($ids)))
            ->map(fn (Contact $contact): array => ['id' => $contact->id, 'name' => $contact->name])
            ->all();
    }

    /**
     * The custom fields a type asks for, in the order its structure places
     * them. A value refused a moment ago is written back into its field; a file
     * cannot be, so it has to be chosen again.
     *
     * @return array<int, array{id: int, label: string, field: Field, rawValue: FieldValue|null, hasOldValue: bool}>
     */
    public function fieldsOf(LifeEventType $lifeEventType): array
    {
        $ownType = $this->lifeEvent?->life_event_type_id === $lifeEventType->id;

        $version = $ownType ? $this->lifeEvent?->structureVersion : $lifeEventType->structure?->publishedVersion;

        $stored = $ownType ? $this->storedValues() : new Collection;

        return ($version?->inputFields() ?? new Collection)
            ->map(fn (Field $field): array => [
                'id' => $field->id,
                'label' => $field->label,
                'field' => $field,
                'rawValue' => $this->oldValueFor($field) ?? $stored->get($field->id),
                'hasOldValue' => old("fields.{$field->id}") !== null || $stored->has($field->id),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function projection(): array
    {
        $oldType = $this->oldType();

        $defaultDate = $this->lifeEvent?->happened_on->toDateString() ?? $this->today();

        return [
            'step' => $oldType instanceof LifeEventType ? 'details' : 'pick',
            'open' => $oldType instanceof LifeEventType,
            'category' => $oldType->life_event_category_id ?? ($this->categories()[0]['id'] ?? null),
            'type' => $oldType instanceof LifeEventType ? [
                'id' => $oldType->id,
                'name' => $oldType->name,
                'categoryName' => $oldType->lifeEventCategory->name,
            ] : null,
            'people' => $this->oldParticipants(),
            'date' => (string) old('happened_on', $defaultDate),
            'today' => $this->today(),
            'types' => collect($this->categories())->flatMap(fn (array $category): array => $category['types'])
                ->map(fn (array $type): array => ['id' => $type['id'], 'name' => $type['name'], 'categoryName' => $type['categoryName']])
                ->values()
                ->all(),
            'openPieces' => [
                ...array_values(array_filter(
                    ['title', 'narrative', 'location', 'duration_in_minutes'],
                    fn (string $piece): bool => $this->pieceValue($piece) !== null,
                )),
                ...collect($oldType instanceof LifeEventType ? $this->fieldsOf($oldType) : [])
                    ->filter(fn (array $field): bool => $field['hasOldValue'])
                    ->map(fn (array $field): string => "field-{$field['id']}")
                    ->values()
                    ->all(),
            ],
            'candidatesUrl' => $this->candidatesUrl(),
            'cancelUrl' => $this->cancelUrl(),
            'resultHeadings' => $this->resultHeadings(),
            'copy' => [
                'noMatch' => __('No type matches “:query”'),
                'keep' => __('The date and the people stay as they are.'),
                'drop' => __('If the new type does not ask for :fields, what you wrote there is dropped.'),
                'or' => __(' or '),
                'remove' => __('Remove :name'),
            ],
        ];
    }

    /** @return array{id: int, name: string, categoryName: string, icon: string, fieldCountLabel: string, fieldsUrl: string} */
    private function typeRow(LifeEventType $type, LifeEventCategory $category): array
    {
        $fieldCount = $this->countFieldsOf($type);

        return [
            'id' => $type->id,
            'name' => $type->name,
            'categoryName' => $category->name,
            'icon' => in_array($type->icon, Section::ICONS, true) ? (string) $type->icon : Section::DEFAULT_ICON,
            'fieldCountLabel' => $fieldCount === 0 ? __('No extra fields') : trans_choice(':count field|:count fields', $fieldCount),
            'fieldsUrl' => route('contacts.lifeEventFields.index', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'lifeEventType' => $type,
                'lifeEvent' => $this->lifeEvent?->uuid,
            ]),
        ];
    }

    private function countFieldsOf(LifeEventType $type): int
    {
        return $type->structure?->publishedVersion?->inputFields()->count() ?? 0;
    }

    /** @return Collection<int, FieldValue> */
    private function storedValues(): Collection
    {
        if (! $this->lifeEvent instanceof LifeEvent) {
            return new Collection;
        }

        return $this->lifeEvent->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem', 'file'])
            ->get()
            ->keyBy('field_id');
    }

    private function oldValueFor(Field $field): ?FieldValue
    {
        $old = old("fields.{$field->id}");
        $column = $field->type->valueColumn();

        if ($old === null || $column === null || $field->type->holdsAFile() || $field->type->isRepeater()) {
            return null;
        }

        return new FieldValue([$column => $old]);
    }
}
