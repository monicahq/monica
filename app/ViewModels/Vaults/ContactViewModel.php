<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\RecordSnapshot;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
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

    public const string PAGE_VERSION_HISTORY = 'version-history';

    private const int HEADER_FACTS = 3;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?string $selectedPageKey = null,
        private readonly ?TemplateVersionSection $editingSection = null,
    ) {}

    /** @return Collection<int, FieldValue> */
    private function values(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem', 'file.vault'])
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

    public function nickname(): ?string
    {
        return $this->contact->nickname;
    }

    public function isFavourite(): bool
    {
        return $this->contact->is_favourite;
    }

    public function isArchived(): bool
    {
        return $this->contact->is_archived;
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
        return route('settings.templateBuilder.index', $this->contact->templateVersion->template);
    }

    /** @return array{number: int, title: string, body: string, label: string, applyUrl: string}|null */
    public function templateUpdate(): ?array
    {
        return once(function (): ?array {
            $current = $this->contact->templateVersion;
            $published = $current->template->publishedVersion;

            if ($published === null || $published->version_number <= $current->version_number) {
                return null;
            }

            $note = $published->change_note;

            return [
                'number' => $published->version_number,
                'title' => __('Version :number of the :template template is ready', [
                    'number' => $published->version_number,
                    'template' => $current->template->name,
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
            'isPinned' => false,
        ]];

        foreach ($this->contact->templateVersion->pages as $page) {
            $fields = $this->fieldsOn($page);

            $tabs[] = [
                'key' => $page->key,
                'label' => $page->label,
                'icon' => 'file',
                'count' => $fields === 0 ? null : $fields,
                'url' => $this->urlTo($page->key),
                'isSelected' => $page->is($selected),
                'isPinned' => false,
            ];
        }

        $tabs[] = [
            'key' => self::PAGE_VERSION_HISTORY,
            'label' => __('Version history'),
            'icon' => 'timeline',
            'count' => null,
            'url' => $this->urlTo(self::PAGE_VERSION_HISTORY),
            'isSelected' => $this->onVersionHistory(),
            'isPinned' => true,
        ];

        return $tabs;
    }

    public function selectedPage(): ?TemplateVersionPage
    {
        return once(function (): ?TemplateVersionPage {
            if (in_array($this->selectedPageKey, [null, self::PAGE_OVERVIEW, self::PAGE_VERSION_HISTORY], true)) {
                return null;
            }

            return $this->contact->templateVersion->pages->firstWhere('key', $this->selectedPageKey);
        });
    }

    public function onOverview(): bool
    {
        return $this->selectedPage() === null && ! $this->onVersionHistory();
    }

    public function onVersionHistory(): bool
    {
        return $this->selectedPageKey === self::PAGE_VERSION_HISTORY;
    }

    /** @return array<int, array<string, mixed>> */
    public function versionHistory(): array
    {
        $chain = $this->versionChain();
        $last = count($chain) - 1;

        $entries = [];

        foreach ($chain as $index => $leg) {
            $version = $leg['version'];
            $until = $chain[$index + 1]['from'] ?? null;
            $isCurrent = $index === $last;

            $entries[] = [
                'date' => $leg['from']->isoFormat('LL'),
                'ago' => $leg['from']->diffForHumans(),
                'title' => __(':template, version :number', [
                    'template' => $version->template->name,
                    'number' => $version->version_number,
                ]),
                'badge' => match (true) {
                    $isCurrent => __('On this version'),
                    $index === 0 => __('First template'),
                    default => null,
                },
                'isCurrent' => $isCurrent,
                'who' => $this->whoApplied($leg, $index === 0),
                'body' => $version->change_note,
                'span' => $this->spanOf($leg['from'], $until, $isCurrent),
                'goBack' => $this->wayBackTo($version, $isCurrent),
                'note' => $isCurrent || $version->status === VersionStatusEnum::Published
                    ? null
                    : __('This version has been retired, so nothing can be put back on it.'),
            ];
        }

        return array_reverse($entries);
    }

    /** @return array<int, array{version: TemplateVersion, from: CarbonImmutable, by: User|null}> */
    private function versionChain(): array
    {
        return once(function (): array {
            $stamps = RecordSnapshot::query()
                ->where('snapshotable_type', $this->contact->getMorphClass())
                ->where('snapshotable_id', $this->contact->id)
                ->with(['templateVersion.template', 'createdBy'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
                ->values()
                ->all();

            $versions = array_map(fn (RecordSnapshot $stamp): TemplateVersion => $stamp->templateVersion, $stamps);
            $versions[] = $this->contact->templateVersion;

            $chain = [['version' => $versions[0], 'from' => $this->contact->created_at, 'by' => null]];

            foreach ($stamps as $index => $stamp) {
                if ($versions[$index + 1]->is($versions[$index])) {
                    continue;
                }

                $chain[] = [
                    'version' => $versions[$index + 1],
                    'from' => $stamp->created_at,
                    'by' => $stamp->createdBy,
                ];
            }

            return $chain;
        });
    }

    /** @param  array{version: TemplateVersion, from: CarbonImmutable, by: User|null}  $leg */
    private function whoApplied(array $leg, bool $isFirst): string
    {
        if ($isFirst) {
            return __('chosen when they were written down');
        }

        if ($leg['by'] === null) {
            return __('applied by the system');
        }

        return $leg['by']->is($this->user)
            ? __('applied by you')
            : __('applied by :name', ['name' => $leg['by']->name]);
    }

    private function spanOf(CarbonImmutable $from, ?CarbonImmutable $until, bool $isCurrent): string
    {
        $duration = $from->diffForHumans(other: $until ?? now(), syntax: CarbonInterface::DIFF_ABSOLUTE);

        return $isCurrent
            ? __('In use for :duration, and counting.', ['duration' => $duration])
            : __('In use for :duration.', ['duration' => $duration]);
    }

    /** @return array{url: string, versionId: int, label: string}|null */
    private function wayBackTo(TemplateVersion $version, bool $isCurrent): ?array
    {
        if ($isCurrent || $version->status !== VersionStatusEnum::Published) {
            return null;
        }

        return [
            'url' => route('contacts.templateVersion.update', [
                'vault' => $this->vault,
                'contact' => $this->contact,
            ]),
            'versionId' => $version->id,
            'label' => __('Move to this version'),
        ];
    }

    /** @return array<int, array{label: string, value: string}> */
    public function versionStats(): array
    {
        $chain = $this->versionChain();

        $templates = collect($chain)
            ->map(fn (array $leg): int => $leg['version']->template_id)
            ->unique()
            ->count();

        return [
            ['label' => __('Templates used'), 'value' => (string) $templates],
            ['label' => __('Versions applied'), 'value' => (string) count($chain)],
            ['label' => __('On this one since'), 'value' => end($chain)['from']->isoFormat('LL')],
        ];
    }

    /** @return array{template: string, number: int, since: string, shows: string} */
    public function currentVersion(): array
    {
        $version = $this->contact->templateVersion;
        $chain = $this->versionChain();

        $sections = $version->assembly->count();
        $fields = $version->assembly
            ->sum(fn (TemplateVersionSection $row): int => $this->storedFields($row->sectionVersion->fields)->count());

        return [
            'template' => $version->template->name,
            'number' => $version->version_number,
            'since' => end($chain)['from']->isoFormat('LL'),
            'shows' => implode(', ', [
                trans_choice(':count section|:count sections', $sections),
                trans_choice(':count field|:count fields', $fields),
            ]),
        ];
    }

    public function setAsideLine(): string
    {
        $count = $this->setAsideCount();

        $invariant = __('A version only decides what this page shows.');

        if ($count === 0) {
            return "{$invariant} ".__('Nothing is hidden. Every answer written here is on a page where it can be seen.');
        }

        return "{$invariant} ".trans_choice('{1}One value written under another version is hidden, and comes back with the version that shows it.|:count values written under other versions are hidden, and come back with the version that shows them.', $count);
    }

    public function setAsideCount(): int
    {
        return once(fn (): int => $this->contact->fieldValues()->where('is_detached', true)->count());
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        $page = $this->selectedPage();

        if ($page === null) {
            return [];
        }

        return $this->contact->templateVersion->assembly
            ->where('template_version_page_id', $page->id)
            ->values()
            ->map(fn (TemplateVersionSection $row): array => $this->blockFor($row))
            ->all();
    }

    /** @return array<string, mixed> */
    private function blockFor(TemplateVersionSection $row): array
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
                'templateVersionSection' => $row,
            ]),
            'saveUrl' => route('contacts.sections.update', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'templateVersionSection' => $row,
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

    private function glyphOf(TemplateVersionSection $row): string
    {
        $icon = $row->sectionVersion->section->icon;

        return in_array($icon, Section::ICONS, true) ? $icon : Section::DEFAULT_ICON;
    }

    private function nameOf(TemplateVersionSection $row): string
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

    public function relationshipLine(): string
    {
        return __('Written down :when.', ['when' => $this->contact->created_at->diffForHumans()]);
    }

    /** @return array<int, array{icon: string, label: string, value: string, reminder: string|null}> */
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
            'value' => $this->contact->last_activity_at?->diffForHumans() ?? __('never'),
            'reminder' => null,
        ];

        return $facts;
    }

    /** @return Collection<int, Field> */
    private function highlightedFields(): Collection
    {
        return once(fn (): Collection => $this->contact->templateVersion->assembly
            ->flatMap(fn (TemplateVersionSection $row): Collection => $this->storedFields($row->sectionVersion->fields))
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

    /** @return array<int, array{icon: string, when: string, title: string, body: string}> */
    public function history(): array
    {
        return [[
            'icon' => 'people',
            'when' => $this->contact->created_at->isoFormat('LL'),
            'title' => __('You added :name', ['name' => $this->contact->name]),
            'body' => __('Everything you write about them from now on appears here, newest first.'),
        ]];
    }

    public function historyNote(): string
    {
        return __('Everything you have written about :name, newest first.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    /** @return array<string, string> */
    public function unbuilt(): array
    {
        $name = $this->contact->first_name ?? $this->contact->name;

        return [
            'compose' => __('A call, a visit, something they said. Writing it down is the next thing this screen will do.'),
            'relationships' => __('Who :name is to the other people in this vault, once relationships exist.', ['name' => $name]),
            'reminders' => __('Birthdays and anything else you ask to be told about, in good time.'),
            'tasks' => __('Things you said you would do for :name appear here until they are done.', ['name' => $name]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fieldsIn(TemplateVersionSection $row): array
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
                'reminder' => $this->reminderFor($field),
                'reminderMark' => $this->reminderMarkOn($field),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    private function reminderFor(Field $field): ?array
    {
        if (! $field->type->canDriveAReminder()) {
            return null;
        }

        return new ContactReminderBlock(
            user: $this->user,
            contact: $this->contact,
            field: $field,
            reminder: $this->reminders()->get($field->id),
        )->get();
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

    /** @return Collection<int, Reminder> */
    private function reminders(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->reminders()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get()
            ->keyBy('field_id')
            ->toBase());
    }

    /** @return Collection<int|string, EloquentCollection<int, FieldValueRow>> */
    private function repetitions(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValueRows()
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault'])])
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

        $children = $field->children->filter(fn (Field $child): bool => $child->type->storesValue());

        return $this->repetitions()
            ->get($field->id, collect())
            ->map(fn (FieldValueRow $row): array => [
                'id' => $row->id,
                'fields' => $children
                    ->map(fn (Field $child): array => $this->answerOn($row, $child))
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function answerOn(FieldValueRow $row, Field $child): array
    {
        $value = $row->values->firstWhere('field_id', $child->id);

        return [
            'id' => $child->id,
            'label' => $child->label,
            'help' => $child->help_text,
            'field' => $child,
            'rawValue' => $value,
            'value' => $value === null ? null : $this->formatter()->format($value),
        ];
    }

    private function fieldsOn(TemplateVersionPage $page): int
    {
        return $this->contact->templateVersion->assembly
            ->where('template_version_page_id', $page->id)
            ->sum(fn (TemplateVersionSection $row): int => $this->storedFields($row->sectionVersion->fields)->count());
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

    private function urlTo(?string $pageKey): string
    {
        return route('contacts.show', array_filter([
            'vault' => $this->vault,
            'contact' => $this->contact,
            'page' => $pageKey,
        ]));
    }
}
