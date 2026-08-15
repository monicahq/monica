<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\VersionStatusEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\RecordSnapshot;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The page of one person: everything written down about them, on the pages the
 * template they render from says they have.
 *
 * The tabs are the version's pages, with an overview in front of them and the
 * version history pinned after them. Answers are read through
 * {@see FieldValueFormatter}, so the page and the form that edits it agree
 * about what a value says.
 *
 * The history, the relationships, the reminders and the tasks are each their
 * own model and none has been written. They are drawn with placeholder content
 * so the screen can be judged as a whole, and every method that builds one says
 * so.
 */
class ContactViewModel
{
    /**
     * The two tabs that belong to no template page. Either can collide with a
     * page key slugified from the same word, and then the two say the same
     * thing about the same record, which is the least surprising collision
     * available.
     */
    public const string PAGE_OVERVIEW = 'overview';

    public const string PAGE_VERSION_HISTORY = 'version-history';

    /**
     * How many highlighted fields the header shows before the rest fall through
     * to the panel down the side. Three fits beside the photograph on the
     * narrowest screen the header is drawn for, and a fourth wraps.
     */
    private const int HEADER_FACTS = 3;

    /** @var list<string> */
    private const array FACT_HUES = ['text-rose', 'text-forest', 'text-sky'];

    /**
     * A section carries no colour of its own, and four panels of identical grey
     * are four panels somebody has to read the heading of to tell apart.
     *
     * @var list<string>
     */
    private const array SECTION_HUES = [
        'bg-indigo-tint text-indigo',
        'bg-forest-tint text-forest',
        'bg-plum-tint text-plum',
        'bg-marigold-tint text-marigold-deep',
        'bg-sky-tint text-sky',
        'bg-rose-tint text-rose',
        'bg-terracotta-tint text-terracotta',
    ];

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?string $selectedPageKey = null,
        private readonly ?TemplateVersionSection $editingSection = null,
    ) {}

    /**
     * Read once for the whole screen. The header, the panels and each page all
     * ask about values, and asking again per field is how a page of forty
     * fields becomes forty queries.
     *
     * The values set aside are left out: they answer a question the page no
     * longer asks, and have a screen of their own.
     *
     * @return Collection<int, FieldValue>
     */
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

    /**
     * The file itself rather than a public address: a picture of somebody is as
     * private as the record it sits on.
     */
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

    public function shareLinksUrl(): string
    {
        return route('contacts.shareLinks.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /**
     * What the share button says. A record nobody is reading from outside says
     * only what the button does; one that is says how many links are open onto
     * it, because that is the fact worth knowing before anything else about
     * sharing.
     */
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

        if ($live === 1) {
            return __('Shared by one link');
        }

        return __('Shared by :count links', ['count' => $live]);
    }

    /**
     * The link to that screen is offered only when there is something on it: a
     * link to a page saying "nothing here" raises a question for no reason.
     */
    public function hasValuesSetAside(): bool
    {
        return once(fn (): bool => $this->contact->fieldValues()->where('is_detached', true)->exists());
    }

    /**
     * The builder, which is an account screen rather than a vault one: a
     * template is shared by every vault, so changing it here changes it for
     * everybody on it.
     */
    public function templateUrl(): string
    {
        return route('settings.templateBuilder.index', $this->contact->templateVersion->template);
    }

    /**
     * The newer version of the template waiting for this record, for the banner
     * that offers it.
     *
     * Nothing is offered when the published version is the one the record is
     * already on, nor when it is older: a record deliberately put back on an
     * earlier version is not asked about it every time somebody opens it.
     *
     * @return array{number: int, title: string, body: string, label: string, applyUrl: string}|null
     */
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
                'body' => trim(($note === null ? '' : "{$note} ").__('Nothing changes here until you apply it. Applying changes which sections and fields :name shows, and everything already written down stays written down.', [
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
     * The tabs: the overview, then one per page of the version, each counting
     * the fields somebody would find on it.
     *
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
            /* A zero is noise on the way to the tabs that have something under
               them, so a page asking nothing carries no number. */
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

        /* Pinned to the far end, away from the pages: they are what the template
           asks about the person, and this is about the template itself. */
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

    /**
     * The page the screen is showing, or null for the two tabs that belong to no
     * template page. An address naming a page this version does not have opens
     * the overview rather than refusing: a tab that has gone is not an error
     * worth a page of its own.
     */
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

    /**
     * Every version this record has rendered from, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
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

    /**
     * The versions the record has been on, oldest first. A snapshot records the
     * version the record was leaving, so the version that follows a snapshot is
     * the next snapshot's, or the record's own for the last of them. A snapshot
     * where those two are the same moved nothing and is skipped.
     *
     * @return array<int, array{version: TemplateVersion, from: Carbon, by: User|null}>
     */
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

            /* One more version than there are snapshots, so the version after a
               snapshot can be read without asking whether it exists. */
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

    /**
     * @param  array{version: TemplateVersion, from: Carbon, by: User|null}  $leg
     */
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

    private function spanOf(Carbon $from, ?Carbon $until, bool $isCurrent): string
    {
        $duration = $from->diffForHumans(other: $until ?? now(), syntax: CarbonInterface::DIFF_ABSOLUTE);

        return $isCurrent
            ? __('In use for :duration, and counting.', ['duration' => $duration])
            : __('In use for :duration.', ['duration' => $duration]);
    }

    /**
     * A retired version is refused as a destination, so it is not offered as
     * one: a button that comes back with an error is worse than a sentence
     * saying why there is no button.
     *
     * @return array{url: string, versionId: int, label: string}|null
     */
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
            'label' => __('Go back to this'),
        ];
    }

    /**
     * The three numbers above the timeline.
     *
     * @return array<int, array{label: string, value: string}>
     */
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

    /**
     * @return array{template: string, number: int, since: string, shows: string}
     */
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
                $sections === 1 ? __('1 section') : __(':count sections', ['count' => $sections]),
                $fields === 1 ? __('1 field') : __(':count fields', ['count' => $fields]),
            ]),
        ];
    }

    /**
     * A version decides what the page shows and nothing else, so a field a
     * later version stopped showing kept its answers. This is where somebody
     * wondering where they went is told.
     */
    public function setAsideLine(): string
    {
        $count = $this->setAsideCount();

        $invariant = __('A version only decides what this page shows.');

        if ($count === 0) {
            return "{$invariant} ".__('Nothing is being kept out of sight: every answer written here is on a page it can be seen on.');
        }

        return "{$invariant} ".($count === 1
            ? __('One value written under another version is kept out of sight, and comes back with the version that shows it.')
            : __(':count values written under other versions are kept out of sight, and come back with the version that shows them.', ['count' => $count]));
    }

    public function setAsideCount(): int
    {
        return once(fn (): int => $this->contact->fieldValues()->where('is_detached', true)->count());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sections(): array
    {
        $page = $this->selectedPage();

        if ($page === null) {
            return [];
        }

        return $this->contact->templateVersion->assembly
            ->where('template_version_page_id', $page->id)
            ->values()
            ->map(fn (TemplateVersionSection $row, int $index): array => $this->blockFor($row, $index))
            ->all();
    }

    /**
     * `isEditable` decides whether the section is offered an edit button at
     * all. One made entirely of types this product cannot yet fill in has
     * nothing to open, and a button leading to read-only sentences is worse
     * than no button.
     *
     * @return array<string, mixed>
     */
    private function blockFor(TemplateVersionSection $row, int $index): array
    {
        $fields = $this->fieldsIn($row);

        return [
            'id' => $row->id,
            /* Opening, cancelling and saving each replace this one id and leave
               every other block alone. */
            'regionId' => "section-{$row->id}",
            'name' => $this->nameOf($row),
            'icon' => $row->sectionVersion->section->icon ?? 'note',
            'iconClass' => self::SECTION_HUES[$index % count(self::SECTION_HUES)],
            'fields' => $fields,
            'isEditing' => $this->editingSection !== null && $this->editingSection->is($row),
            'isEditable' => collect($fields)->contains(fn (array $field): bool => $field['isEditable']),
            /* The encoding is set once, on the form, so this is a property of
               the block rather than of one control. */
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

    /**
     * The override is display only: a section is shared, so renaming it from
     * inside one template would reach into every other template carrying it.
     */
    private function nameOf(TemplateVersionSection $row): string
    {
        return $row->title_override ?? $row->sectionVersion->section->name;
    }

    /**
     * Cancelling an edit goes back here, and therefore reloads whatever was
     * last saved.
     */
    public function pageUrl(): string
    {
        return $this->urlTo($this->selectedPage()?->key);
    }

    /**
     * A page with no section is one somebody made and has not filled yet, which
     * deserves a sentence rather than an empty rectangle.
     */
    public function emptyPageLine(): string
    {
        return __('This page has no sections yet. What it asks about :name is decided on the template.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    /**
     * The sentence under the name. Placeholder until relationships exist: how
     * two people are related is its own model, and until it is written the
     * header says how long the person has been written down instead, which is
     * true and comes from the row itself.
     */
    public function relationshipLine(): string
    {
        return __('Written down :when.', ['when' => $this->contact->created_at->diffForHumans()]);
    }

    /**
     * The facts beside the photograph. The highlighted fields are read across
     * every page of the version rather than only the open one: a field put in
     * the header is meant to stay there whatever tab somebody walks to.
     *
     * @return array<int, array{icon: string, label: string, value: string, iconClass: string}>
     */
    public function headerFacts(): array
    {
        $facts = $this->highlightedFields()
            ->take(self::HEADER_FACTS)
            ->values()
            ->map(fn (Field $field, int $index): array => [
                'icon' => 'file',
                'label' => $field->label,
                'value' => $this->valueOf($field) ?? __('Not recorded'),
                'iconClass' => self::FACT_HUES[$index % count(self::FACT_HUES)],
            ])
            ->all();

        $facts[] = [
            'icon' => 'timeline',
            'label' => __('Last in touch'),
            'value' => $this->contact->last_activity_at?->diffForHumans() ?? __('never'),
            'iconClass' => 'text-muted',
        ];

        return $facts;
    }

    /**
     * @return Collection<int, Field>
     */
    private function highlightedFields(): Collection
    {
        return once(fn (): Collection => $this->contact->templateVersion->assembly
            ->flatMap(fn (TemplateVersionSection $row): Collection => $this->storedFields($row->sectionVersion->fields))
            ->filter(fn (Field $field): bool => $field->is_highlighted)
            ->values());
    }

    /**
     * The facts panel down the right: the nickname, which the product imposes,
     * and every highlighted field the header had no room for. What is asked
     * about somebody is what their template asks, so nothing is listed here.
     *
     * @return array<int, array{icon: string, label: string, value: string|null}>
     */
    public function facts(): array
    {
        $facts = [['icon' => 'file', 'label' => __('Nickname'), 'value' => $this->contact->nickname]];

        foreach ($this->highlightedFields()->skip(self::HEADER_FACTS) as $field) {
            $facts[] = [
                'icon' => 'info',
                'label' => $field->label,
                'value' => $this->valueOf($field),
            ];
        }

        return $facts;
    }

    /**
     * Placeholder until activities exist. The one thing the row knows is when
     * the person was written down, so that is the single entry.
     *
     * @return array<int, array{icon: string, iconClass: string, when: string, title: string, body: string}>
     */
    public function history(): array
    {
        return [[
            'icon' => 'people',
            'iconClass' => 'bg-indigo-tint text-indigo',
            'when' => $this->contact->created_at->isoFormat('LL'),
            'title' => __('You wrote :name down', ['name' => $this->contact->name]),
            'body' => __('Everything you write about them from now on lands here, newest first.'),
        ]];
    }

    public function historyNote(): string
    {
        return __('Everything you have written about :name, newest first.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    /**
     * The sentence in each panel that has no model behind it. Each says what
     * will land there rather than merely that it is empty.
     *
     * @return array<string, string>
     */
    public function unbuilt(): array
    {
        $name = $this->contact->first_name ?? $this->contact->name;

        return [
            'compose' => __('A call, a visit, something they said. Writing it down is the next thing this screen learns to do.'),
            'relationships' => __('Who :name is to the other people in this vault, once relationships exist.', ['name' => $name]),
            'reminders' => __('Birthdays and anything else you ask to be told about, so December is not a panic.'),
            'tasks' => __('Things you said you would do for :name land here until they are done.', ['name' => $name]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
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
            ])
            ->values()
            ->all();
    }

    /**
     * Read once for the whole screen, for the same reason the plain values are.
     *
     * @return Collection<int|string, EloquentCollection<int, FieldValueRow>>
     */
    private function repetitions(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValueRows()
            ->with(['values' => fn ($query) => $query->attached()->with(['field', 'valueListItem', 'file.vault'])])
            ->orderBy('position')
            ->get()
            ->groupBy('field_id')
            ->toBase());
    }

    /**
     * What one repeater holds, in the order somebody put it in. A field that is
     * not a repeater reads as the same empty array: what the panel needs to
     * know is only whether there is anything to draw.
     *
     * @return array<int, array{id: int, fields: array<int, array<string, mixed>>}>
     */
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

    /**
     * @return array<string, mixed>
     */
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
     * The fields that hold a value. A divider or a heading breaks a long form
     * up and carries nothing, so the record has nothing to say about it.
     *
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
