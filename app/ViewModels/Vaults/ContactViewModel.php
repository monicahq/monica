<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\FieldValueFormatter;
use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\ContactMerge;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\LifeEvent;
use App\Models\RecordSnapshot;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\Section;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ContactViewModel
{
    public const string PAGE_OVERVIEW = 'overview';

    public const string PAGE_RELATIONSHIPS = 'relationships';

    public const string PAGE_VERSION_HISTORY = 'version-history';

    private const int HEADER_FACTS = 3;

    /** @var array<int, ContactFieldRowsViewModel> */
    private array $fieldRows = [];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?string $selectedPageKey = null,
        private readonly ?StructureVersionSection $editingSection = null,
        private readonly int $timelinePage = 1,
        private readonly ?int $lifeEventJustAddedId = null,
        private readonly ?LifeEvent $editingLifeEvent = null,
        private readonly ?int $lifeEventEditedId = null,
        private readonly ?string $deletedLifeEventUuid = null,
        private readonly ?string $deletedLifeEventLine = null,
        private readonly bool $opensLifeEventPanel = false,
        private readonly bool $opensReminderForm = false,
    ) {}

    /** @return Collection<int, FieldValue> */
    private function values(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])
            ->get()
            ->keyBy('field_id'));
    }

    private function valueOf(Field $field): ?string
    {
        $value = $this->values()->get($field->id);

        return $value === null ? null : $this->formatter()->format($value);
    }

    private function formatter(): FieldValueFormatter
    {
        return once(fn (): FieldValueFormatter => new FieldValueFormatter($this->user));
    }

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return $this->contact->name;
    }

    public function isFavourite(): bool
    {
        return $this->contact->is_favourite;
    }

    public function isArchived(): bool
    {
        return $this->contact->is_archived;
    }

    public function isMe(): bool
    {
        return $this->contact->is_me;
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function avatarUrl(): ?string
    {
        if ($this->contact->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->vault, 'file' => $this->contact->avatar]);
    }

    public function editUrl(): string
    {
        return route('contacts.edit', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function setAsideUrl(): string
    {
        return route('contacts.setAside.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function favouriteUrl(): string
    {
        return route('contacts.favourite.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function favouriteLabel(): string
    {
        return $this->isFavourite()
            ? __('Remove :name from favourites', ['name' => $this->contact->name])
            : __('Add :name to favourites', ['name' => $this->contact->name]);
    }

    public function shareLinksUrl(): string
    {
        return route('contacts.shareLinks.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function mergeUrl(): string
    {
        return route('contacts.merge.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function vCardUrl(): ?string
    {
        if (! FlagState::isOn('vcard-export', $this->user->account)) {
            return null;
        }

        return route('contacts.vcard.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function versionHistoryUrl(): string
    {
        return $this->urlTo(self::PAGE_VERSION_HISTORY);
    }

    public function undoableMerge(): ?ContactMerge
    {
        return once(fn (): ?ContactMerge => ContactMerge::query()
            ->where('kept_contact_id', $this->contact->id)
            ->whereNull('undone_at')
            ->latest('id')
            ->first());
    }

    public function undoMergeUrl(): ?string
    {
        $merge = $this->undoableMerge();

        return $merge === null ? null : route('vaults.merges.destroy', [
            'vault' => $this->vault,
            'contactMerge' => $merge,
        ]);
    }

    public function undoMergeLine(): string
    {
        return __('Absorbed :name into this record.', ['name' => $this->undoableMerge()?->mergedContact->name]);
    }

    public function shareLine(): string
    {
        $live = once(fn (): int => $this->contact
            ->shareLinks()
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count());

        if ($live === 0) {
            return __('Share');
        }

        return trans_choice('{1}Shared by one link|Shared by :count links', $live);
    }

    public function hasValuesSetAside(): bool
    {
        return once(fn (): bool => $this->contact->fieldValues()->where('is_detached', true)->exists());
    }

    public function templateUrl(): string
    {
        return route('settings.templateBuilder.index', $this->contact->structureVersion->structure->owner);
    }

    /** @return array{number: int, title: string, body: string, label: string, applyUrl: string}|null */
    public function templateUpdate(): ?array
    {
        return once(function (): ?array {
            $current = $this->contact->structureVersion;
            $structure = $current->structure;
            $published = $structure->published_version_id === $current->id ? $current : $structure->publishedVersion;

            if ($published === null || $published->version_number <= $current->version_number) {
                return null;
            }

            $note = $published->change_note;

            return [
                'number' => $published->version_number,
                'title' => __('Version :number of the :template template is ready', [
                    'number' => $published->version_number,
                    'template' => $current->structure->owner->name,
                ]),
                'body' => trim(($note === null ? '' : "{$note} ").__('Nothing changes here until you apply it. Applying it changes which sections and fields :name shows. Everything already written down stays.', [
                    'name' => $this->contact->first_name ?? $this->contact->name,
                ])),
                'label' => __('Apply version :number', ['number' => $published->version_number]),
                'applyUrl' => route('contacts.templateVersion.update', [
                    'vault' => $this->vault,
                    'contact' => $this->contact,
                ]),
            ];
        });
    }

    /**
     * @return array<int, array{key: string, label: string, icon: string, count: int|null, url: string, isSelected: bool}>
     */
    public function pages(): array
    {
        $selected = $this->selectedPage();

        $tabs = [[
            'key' => self::PAGE_OVERVIEW,
            'label' => __('Overview'),
            'icon' => 'people',
            'count' => null,
            'url' => $this->urlTo(null),
            'isSelected' => $this->onOverview(),
        ]];

        foreach ($this->contact->structureVersion->pages as $page) {
            $fields = $this->fieldsOn($page);

            $tabs[] = [
                'key' => $page->key,
                'label' => $page->label,
                'icon' => 'file',
                'count' => $fields === 0 ? null : $fields,
                'url' => $this->urlTo($page->key),
                'isSelected' => $page->is($selected),
            ];
        }

        $tabs[] = [
            'key' => self::PAGE_RELATIONSHIPS,
            'label' => __('Relationships'),
            'icon' => 'relationship',
            'count' => $this->relationships()->total() === 0 ? null : $this->relationships()->total(),
            'url' => $this->urlTo(self::PAGE_RELATIONSHIPS),
            'isSelected' => $this->onRelationships(),
        ];

        return $tabs;
    }

    public function selectedPage(): ?StructureVersionPage
    {
        return once(function (): ?StructureVersionPage {
            if (in_array($this->selectedPageKey, [null, self::PAGE_OVERVIEW, self::PAGE_RELATIONSHIPS, self::PAGE_VERSION_HISTORY], true)) {
                return null;
            }

            return $this->contact->structureVersion->pages->firstWhere('key', $this->selectedPageKey);
        });
    }

    public function onOverview(): bool
    {
        return $this->selectedPage() === null && ! $this->onVersionHistory() && ! $this->onRelationships();
    }

    public function onRelationships(): bool
    {
        return $this->selectedPageKey === self::PAGE_RELATIONSHIPS;
    }

    public function onVersionHistory(): bool
    {
        return $this->selectedPageKey === self::PAGE_VERSION_HISTORY;
    }

    /** @return array<int, array{date: string, ago: string, title: string, isCurrent: bool, span: string, revert: array{url: string, versionId: int, title: string, confirm: string, cancel: string, body: string}|null, refusal: string|null, spaceBelow: int}> */
    public function versionHistory(): array
    {
        $chain = $this->versionChain();
        $last = count($chain) - 1;

        $entries = [];

        foreach ($chain as $index => $leg) {
            $version = $leg['version'];
            $until = $chain[$index + 1]['from'] ?? null;
            $isCurrent = $index === $last;
            $title = __(':template, version :number', [
                'template' => $version->structure->owner->name,
                'number' => $version->version_number,
            ]);

            $entries[] = [
                'date' => $leg['from']->isoFormat('LL'),
                'ago' => $leg['from']->diffForHumans(),
                'title' => $title,
                'isCurrent' => $isCurrent,
                'span' => $this->spanOf($leg['from'], $until, $isCurrent),
                'revert' => $this->revertTo($version, $title, $isCurrent),
                'refusal' => $this->whyNoRevertTo($version, $isCurrent),
                'spaceBelow' => $index === 0 ? 0 : $this->spaceFor(from: $chain[$index - 1]['from'], until: $leg['from']),
            ];
        }

        return array_reverse($entries);
    }

    /** @return array<int, array{version: StructureVersion, from: CarbonImmutable}> */
    private function versionChain(): array
    {
        return once(function (): array {
            $stamps = RecordSnapshot::query()
                ->where('snapshotable_type', $this->contact->getMorphClass())
                ->where('snapshotable_id', $this->contact->id)
                ->with('structureVersion.structure.owner')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->values()
                ->all();

            $versions = array_map(fn (RecordSnapshot $stamp): StructureVersion => $stamp->structureVersion, $stamps);
            $versions[] = $this->contact->structureVersion;

            $chain = [['version' => $versions[0], 'from' => $this->contact->created_at]];

            foreach ($stamps as $index => $stamp) {
                if ($versions[$index + 1]->is($versions[$index])) {
                    continue;
                }

                $chain[] = [
                    'version' => $versions[$index + 1],
                    'from' => $stamp->created_at,
                ];
            }

            return $chain;
        });
    }

    private function spanOf(CarbonImmutable $from, ?CarbonImmutable $until, bool $isCurrent): string
    {
        $duration = $from->diffForHumans(other: $until ?? now(), syntax: CarbonInterface::DIFF_ABSOLUTE);

        return $isCurrent
            ? __('In use for :duration, and counting.', ['duration' => $duration])
            : __('In use for :duration.', ['duration' => $duration]);
    }

    private function spaceFor(CarbonImmutable $from, CarbonImmutable $until): int
    {
        return (int) min(96, 20 + round($from->diffInDays($until) / 2));
    }

    private function canRevertTo(StructureVersion $version, bool $isCurrent): bool
    {
        return ! $isCurrent
            && $version->status === VersionStatusEnum::Published
            && $version->structure_id === $this->contact->structureVersion->structure_id;
    }

    /** @return array{url: string, versionId: int, title: string, confirm: string, cancel: string, body: string}|null */
    private function revertTo(StructureVersion $version, string $title, bool $isCurrent): ?array
    {
        if (! $this->canRevertTo($version, $isCurrent)) {
            return null;
        }

        return [
            'url' => route('contacts.templateVersion.update', [
                'vault' => $this->vault,
                'contact' => $this->contact,
            ]),
            'versionId' => $version->id,
            'title' => __('Revert to :version?', ['version' => $title]),
            'confirm' => __('Revert'),
            'cancel' => __('Stay on version :number', ['number' => $this->contact->structureVersion->version_number]),
            'body' => __('This contact will show the sections and fields of version :number again. Answers in fields that version does not have are hidden, not deleted, and come back when you move to a version that has them. This is recorded in the version history.', [
                'number' => $version->version_number,
            ]),
        ];
    }

    private function whyNoRevertTo(StructureVersion $version, bool $isCurrent): ?string
    {
        if ($isCurrent || $this->canRevertTo($version, $isCurrent)) {
            return null;
        }

        if ($version->status !== VersionStatusEnum::Published) {
            return __('This version has been retired, so nothing can be put back on it.');
        }

        return __('This version belongs to another template, so it cannot be reverted to here.');
    }

    public function setAsideLine(): ?string
    {
        $count = $this->setAsideCount();

        if ($count === 0) {
            return null;
        }

        return trans_choice('{1}One value written under another version is hidden.|:count values written under other versions are hidden.', $count);
    }

    public function setAsideCount(): int
    {
        return once(fn (): int => $this->contact->fieldValues()->where('is_detached', true)->count());
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        return once(function (): array {
            $page = $this->selectedPage();

            if ($page === null) {
                return [];
            }

            return $this->contact->structureVersion->assembly
                ->where('structure_version_page_id', $page->id)
                ->values()
                ->map(fn (StructureVersionSection $row): array => $this->blockFor($row))
                ->all();
        });
    }

    /**
     * Whether the panels an Alpine Ajax request targets can be drawn without the rest of the
     * page. A target the panels view does not know falls back to the whole page, because Alpine
     * Ajax removes a target the response does not carry.
     *
     * @param  list<string>  $targets
     */
    public function drawsTargetsAlone(array $targets): bool
    {
        if ($targets === []) {
            return false;
        }

        $drawable = ['notifications', 'contact-favourite', 'contact-facts'];

        if ($this->onOverview()) {
            $drawable = [...$drawable, 'contact-timeline', 'contact-timeline-rows', 'contact-timeline-pagination', 'contact-reminder-panel'];
        }

        $drawable = [...$drawable, ...array_column($this->sections(), 'regionId')];

        return array_diff($targets, $drawable) === [];
    }

    /** @return array<string, mixed> */
    private function blockFor(StructureVersionSection $row): array
    {
        $fields = $this->fieldsIn($row);

        return [
            'id' => $row->id,
            'regionId' => "section-{$row->id}",
            'name' => $this->nameOf($row),
            'icon' => $this->glyphOf($row),
            'tint' => VaultColourEnum::tintFor($row->sectionVersion->section->colour),
            'fields' => $fields,
            'isEditing' => $this->editingSection !== null && $this->editingSection->is($row),
            'isEditable' => collect($fields)->contains(fn (array $field): bool => $field['isEditable']),
            'holdsAFile' => collect($fields)->contains(fn (array $field): bool => $this->asksForAFile($field['field'])),
            'editUrl' => route('contacts.sections.edit', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'structureVersionSection' => $row,
            ]),
            'saveUrl' => route('contacts.sections.update', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'structureVersionSection' => $row,
            ]),
            'saveLine' => __('Saving writes :section only.', ['section' => mb_strtolower($this->nameOf($row))]),
        ];
    }

    private function asksForAFile(Field $field): bool
    {
        if ($field->type->holdsAFile()) {
            return true;
        }

        return $field->type->isRepeater()
            && $field->children->contains(fn (Field $child): bool => $child->type->holdsAFile());
    }

    private function glyphOf(StructureVersionSection $row): string
    {
        $icon = $row->sectionVersion->section->icon;

        return in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON;
    }

    private function nameOf(StructureVersionSection $row): string
    {
        return $row->title_override ?? $row->sectionVersion->section->name;
    }

    public function pageUrl(): string
    {
        return $this->urlTo($this->selectedPage()?->key);
    }

    public function emptyPageLine(): string
    {
        return __('This page has no sections yet. What it asks about :name is decided on the template.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    /** @return array<int, array{icon: string, label: string, value: string, reminder: string|null, url?: string}> */
    public function headerFacts(): array
    {
        $facts = $this->highlightedFields()
            ->take(self::HEADER_FACTS)
            ->values()
            ->map(fn (Field $field): array => [
                'icon' => 'file',
                'label' => $field->label,
                'value' => $this->valueOf($field) ?? __('Not recorded'),
                'reminder' => $this->reminderMarkOn($field),
            ])
            ->all();

        $facts[] = [
            'icon' => 'timeline',
            'label' => __('Last in touch'),
            'value' => $this->contact->last_interaction_at?->diffForHumans() ?? __('never'),
            'reminder' => null,
        ];

        $awaiting = $this->awaitingAnswer();

        $facts[] = [
            'icon' => 'reminder',
            'label' => __('Reminders'),
            'value' => $awaiting === 0
                ? __('none waiting')
                : trans_choice(':count awaiting an answer|:count awaiting an answer', $awaiting),
            'reminder' => null,
            'url' => route('contacts.reminders.index', ['vault' => $this->vault, 'contact' => $this->contact]),
        ];

        return $facts;
    }

    /** @return Collection<int, Field> */
    private function highlightedFields(): Collection
    {
        return once(fn (): Collection => $this->contact->structureVersion->assembly
            ->flatMap(fn (StructureVersionSection $row): Collection => $this->storedFields($row->sectionVersion->fields))
            ->filter(fn (Field $field): bool => $field->is_highlighted)
            ->values());
    }

    /** @return array<int, array{icon: string, label: string, value: string|null, reminder: string|null}> */
    public function facts(): array
    {
        $facts = [['icon' => 'file', 'label' => __('Nickname'), 'value' => $this->contact->nickname, 'reminder' => null]];

        foreach ($this->highlightedFields()->skip(self::HEADER_FACTS) as $field) {
            $facts[] = [
                'icon' => 'info',
                'label' => $field->label,
                'value' => $this->valueOf($field),
                'reminder' => $this->reminderMarkOn($field),
            ];
        }

        return $facts;
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldsIn(StructureVersionSection $row): array
    {
        return $this->storedFields($row->sectionVersion->fields)
            ->map(fn (Field $field): array => [
                'id' => $field->id,
                'label' => $field->label,
                'help' => $field->help_text,
                'typeLabel' => FieldCatalogueViewModel::labelFor($field->type),
                'value' => $this->valueOf($field),
                'field' => $field,
                'rawValue' => $this->values()->get($field->id),
                'isEditable' => $field->type->hasAnInput(),
                'rows' => $this->repetitionsOf($field),
                'rowsMore' => $this->editingSection === null && $field->type->isRepeater() ? $this->fieldRows($field)->more() : null,
                'notes' => $this->notesOf($field, $row),
                'reminder' => $this->reminderFor($field),
                'reminderMark' => $this->reminderMarkOn($field),
            ])
            ->values()
            ->all();
    }

    /** @return array{showsLabel: bool, entries: array<int, array{id: int, shown: array<int, string>, hidden: array<int, string>, addedLine: string}>}|null */
    private function notesOf(Field $field, StructureVersionSection $row): ?array
    {
        if (! $this->fieldRows($field)->readsAsNotes()) {
            return null;
        }

        return [
            'showsLabel' => $field->label !== $this->nameOf($row),
            'entries' => $this->fieldRows($field)->noteEntries(),
        ];
    }

    private function fieldRows(Field $field): ContactFieldRowsViewModel
    {
        return $this->fieldRows[$field->id] ??= new ContactFieldRowsViewModel(
            user: $this->user,
            vault: $this->vault,
            contact: $this->contact,
            field: $field,
        );
    }

    private function reminderFor(Field $field): ?ContactReminderBlock
    {
        if (! $field->type->canDriveAReminder()) {
            return null;
        }

        return new ContactReminderBlock(
            user: $this->user,
            contact: $this->contact,
            field: $field,
            reminder: $this->reminders()->get($field->id),
        );
    }

    private function reminderMarkOn(Field $field): ?string
    {
        if (! $field->type->canDriveAReminder()) {
            return null;
        }

        return new ContactReminderBlock(
            user: $this->user,
            contact: $this->contact,
            field: $field,
            reminder: $this->reminders()->get($field->id),
        )->mark();
    }

    private function awaitingAnswer(): int
    {
        return ReminderOccurrence::query()
            ->whereIn('reminder_id', $this->contact->reminders()->active()->select('id'))
            ->active()
            ->where('local_date', '<', CarbonImmutable::now($this->user->timezone)->addDay()->toDateString())
            ->count();
    }

    /** @return Collection<int, Reminder> */
    private function reminders(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->reminders()
            ->whereNotNull('field_id')
            ->orderBy('is_active')
            ->orderBy('id')
            ->get()
            ->keyBy('field_id')
            ->toBase());
    }

    /** @return Collection<int|string, EloquentCollection<int, FieldValueRow>> */
    private function repetitions(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValueRows()
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault', 'relatedContact', 'relatedEntity'])])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->groupBy('field_id')
            ->toBase());
    }

    /** @return array<int, array{id: int, fields: array<int, array<string, mixed>>}> */
    private function repetitionsOf(Field $field): array
    {
        if (! $field->type->isRepeater()) {
            return [];
        }

        if ($this->editingSection === null) {
            return $this->fieldRows($field)->shapedRows();
        }

        return $this->repetitions()
            ->get($field->id, collect())
            ->map(fn (FieldValueRow $row): array => $this->fieldRows($field)->shapeRow($row))
            ->values()
            ->all();
    }

    private function fieldsOn(StructureVersionPage $page): int
    {
        return $this->contact->structureVersion->assembly
            ->where('structure_version_page_id', $page->id)
            ->sum(fn (StructureVersionSection $row): int => $this->storedFields($row->sectionVersion->fields)->count());
    }

    /**
     * @param  Collection<int, Field>  $fields
     * @return Collection<int, Field>
     */
    private function storedFields(Collection $fields): Collection
    {
        return $fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->storesValue());
    }

    public function addRelationshipUrl(): string
    {
        return route('contacts.relationships.new', [
            'vault' => $this->vault,
            'contact' => $this->contact,
        ]);
    }

    public function immediateFamily(): ImmediateFamilyBlock
    {
        return once(fn (): ImmediateFamilyBlock => new ImmediateFamilyBlock(
            vault: $this->vault,
            contact: $this->contact,
        ));
    }

    public function deadlines(): ContactDeadlinesBlock
    {
        return once(fn (): ContactDeadlinesBlock => new ContactDeadlinesBlock(
            user: $this->user,
            vault: $this->vault,
            contact: $this->contact,
            isAskedFor: $this->opensReminderForm,
        ));
    }

    public function timeline(): ContactTimelineBlock
    {
        return once(fn (): ContactTimelineBlock => new ContactTimelineBlock(
            user: $this->user,
            vault: $this->vault,
            contact: $this->contact,
            page: max(1, $this->timelinePage),
            justAddedId: $this->lifeEventJustAddedId,
            editingId: $this->editingLifeEvent?->id,
            editedId: $this->lifeEventEditedId,
        ));
    }

    public function lifeEventForm(): LifeEventFormBlock
    {
        return once(fn (): LifeEventFormBlock => new LifeEventFormBlock(
            user: $this->user,
            vault: $this->vault,
            contact: $this->contact,
            lifeEvent: $this->editingLifeEvent,
            isAskedFor: $this->opensLifeEventPanel,
        ));
    }

    /**
     * The life event deleted a moment ago, with the way to take it back. It is
     * offered once: after a reload the flash is gone, and so is the offer.
     *
     * @return array{line: string, restoreUrl: string}|null
     */
    public function deletedLifeEvent(): ?array
    {
        if ($this->deletedLifeEventUuid === null || $this->deletedLifeEventLine === null) {
            return null;
        }

        return [
            'line' => $this->deletedLifeEventLine,
            'restoreUrl' => route('contacts.lifeEventRestoration.update', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'lifeEventUuid' => $this->deletedLifeEventUuid,
            ]),
        ];
    }

    public function relationships(): RelationshipsBlock
    {
        return once(fn (): RelationshipsBlock => new RelationshipsBlock(
            vault: $this->vault,
            contact: $this->contact,
        ));
    }

    public function familyTree(): FamilyTreeBlock
    {
        return once(fn (): FamilyTreeBlock => new FamilyTreeBlock(
            vault: $this->vault,
            contact: $this->contact,
        ));
    }

    private function urlTo(?string $pageKey): string
    {
        return route('contacts.show', array_filter([
            'vault' => $this->vault,
            'contact' => $this->contact,
            'page' => $pageKey,
        ]));
    }
}
