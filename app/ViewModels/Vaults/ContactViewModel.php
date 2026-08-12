<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The page of one person: everything written down about them, on the pages the
 * template they render from says they have.
 *
 * The tabs are the version's pages with an overview in front of them. The
 * overview belongs to no template and is the same on everybody: what you have
 * written about them down one column, and what you know about them down the
 * other. The pages after it are the template's, and each is the sections on it
 * with the fields inside them.
 *
 * What the template asks is real: the pages, the sections, the fields and now
 * the answers, which are read through {@see FieldValueFormatter} so the page
 * and the form that edits it agree about what a value says.
 *
 * The rest of the screen has no model behind it yet. The history, the
 * relationships, the reminders and the tasks are each their own model and none
 * of them has been written; they are drawn here with placeholder content so the
 * screen can be judged as a whole, and every method that builds one says so.
 * The controls that would write any of it are drawn and not offered, the way
 * the dock draws a destination that does not exist.
 */
class ContactViewModel
{
    /**
     * The key of the tab that belongs to no template page. It cannot collide
     * with a page's key, which is slugified from a label somebody typed, unless
     * they name a page "Overview" — and then the two say the same word about the
     * same record, which is the least surprising collision available.
     */
    public const string PAGE_OVERVIEW = 'overview';

    /**
     * The hues a section's mark is drawn in, taken in turn down the page. A
     * section carries no colour of its own, and four panels of identical grey
     * are four panels somebody has to read the heading of to tell apart.
     */
    /**
     * How many highlighted fields the header shows before the rest fall through
     * to the panel down the side. Three fits beside the photograph on the
     * narrowest screen the header is drawn for, and a fourth wraps.
     */
    private const int HEADER_FACTS = 3;

    /**
     * The colours the header facts are marked in, taken in turn.
     *
     * @var list<string>
     */
    private const array FACT_HUES = ['text-rose', 'text-forest', 'text-sky'];

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
     * Everything written on the record, keyed by the field it answers, read
     * once for the whole screen. The header, the panels and each page all ask
     * about values, and asking the database again for every field of every
     * section is how a page of forty fields becomes forty queries.
     *
     * The values set aside are left out. A field that has left the structure
     * has a screen of its own that explains why; on this one it would be an
     * answer to a question the page no longer asks.
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

    /**
     * What one field reads as, or null when nothing has been written in it.
     */
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
     * Where the two things the template has no say over are handled: the name,
     * and whether the person is on the record at all. Both are edited somewhere
     * the template has no say over either, and both behind one way in: the page
     * is about a person rather than about the things that can be done to a row.
     */
    public function editUrl(): string
    {
        return route('contacts.edit', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function setAsideUrl(): string
    {
        return route('contacts.setAside.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /**
     * Whether anything is being kept for a question the structure has stopped
     * asking. The link to that screen is offered only when it is, since a link
     * to a page that says "nothing here" raises a question for no reason.
     */
    public function hasValuesSetAside(): bool
    {
        return once(fn (): bool => $this->contact->fieldValues()->where('is_detached', true)->exists());
    }

    /**
     * Where the pages of this record are decided. It is the builder, which is
     * an account screen rather than a vault one: a template is shared by every
     * vault, so changing it here changes it for everybody on it.
     *
     * It is offered from the one place on the record that has to explain itself,
     * which is a page the template puts nothing on. The record no longer names
     * its template anywhere else: which structure somebody renders from is not a
     * fact about them.
     */
    public function templateUrl(): string
    {
        return route('settings.templateBuilder.index', $this->contact->templateVersion->template);
    }

    /**
     * The newer version of the template waiting for this record, when there is
     * one, as the banner that offers it needs it.
     *
     * A record renders from one version and stays on it: publishing a new
     * version of a template moves nobody. That is what makes editing a template
     * safe, and it is also why the record has to say so, or somebody who has
     * changed a template sees no sign of it on the people it describes.
     *
     * Nothing is offered when the published version is the one the record is
     * already on, and nothing is offered when it is older than the record's: a
     * record deliberately put back on an earlier version is not asked about it
     * every time somebody opens it.
     *
     * @return array{title: string, body: string, label: string, applyUrl: string}|null
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
            'isSelected' => $selected === null,
        ]];

        foreach ($this->contact->templateVersion->pages as $page) {
            /* A page the template asks nothing on carries no number. Nothing is
               waiting there, and a zero is noise on the way to the tabs that
               have something under them. */
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

        return $tabs;
    }

    /**
     * The page the screen is showing, or null for the overview. An address
     * naming a page this version does not have opens the overview rather than
     * refusing: a tab that has gone is not an error worth a page of its own.
     */
    public function selectedPage(): ?TemplateVersionPage
    {
        return once(function (): ?TemplateVersionPage {
            if ($this->selectedPageKey === null || $this->selectedPageKey === self::PAGE_OVERVIEW) {
                return null;
            }

            return $this->contact->templateVersion->pages->firstWhere('key', $this->selectedPageKey);
        });
    }

    public function onOverview(): bool
    {
        return $this->selectedPage() === null;
    }

    /**
     * The sections on the open page, in order, each with the fields inside it.
     *
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
     * One section, as the panel that draws it needs it: what it is called, the
     * fields inside it, and the three addresses that make it a block somebody
     * can open, write and walk away from.
     *
     * `isEditable` is what decides whether it is offered an edit button at all.
     * A section made entirely of types this product cannot yet fill in has
     * nothing to open, and a button that leads to a form of read-only sentences
     * is worse than no button.
     *
     * @return array<string, mixed>
     */
    private function blockFor(TemplateVersionSection $row, int $index): array
    {
        $fields = $this->fieldsIn($row);

        return [
            'id' => $row->id,
            /* The panel is its own region: opening, cancelling and saving each
               replace this one id and leave every other block alone, including
               one somebody has already opened further down the page. */
            'regionId' => "section-{$row->id}",
            'name' => $this->nameOf($row),
            'icon' => $row->sectionVersion->section->icon ?? 'note',
            'iconClass' => self::SECTION_HUES[$index % count(self::SECTION_HUES)],
            'fields' => $fields,
            'isEditing' => $this->editingSection !== null && $this->editingSection->is($row),
            'isEditable' => collect($fields)->contains(fn (array $field): bool => $field['isEditable']),
            /* Whether the form that edits this block has to be able to carry an
               upload, which is a property of the block rather than of one
               control: the encoding is set once, on the form. */
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

    /**
     * Whether a field takes a file, itself or on each of its repetitions.
     */
    private function asksForAFile(Field $field): bool
    {
        if ($field->type->holdsAFile()) {
            return true;
        }

        return $field->type->isRepeater()
            && $field->children->contains(fn (Field $child): bool => $child->type->holdsAFile());
    }

    /**
     * What a section is called here, which is the title this template gives it
     * when it gives it one. The override is display only: a section is shared,
     * so renaming it from inside one template would reach into every other
     * template carrying it.
     */
    private function nameOf(TemplateVersionSection $row): string
    {
        return $row->title_override ?? $row->sectionVersion->section->name;
    }

    /**
     * The address of the open page, which cancelling an edit goes back to and
     * which therefore reloads whatever was last saved.
     */
    public function pageUrl(): string
    {
        return $this->urlTo($this->selectedPage()?->key);
    }

    /**
     * What the open page says when the template puts nothing on it. A page with
     * no section is a page somebody made and has not filled yet, which is a
     * sentence rather than an empty rectangle.
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
     * The facts beside the photograph: the fields the template marks as
     * highlighted, and how long it has been since the last real interaction.
     *
     * The highlighted ones are read across every page of the version rather
     * than only the open one. A field put in the header is meant to stay there,
     * and walking between tabs must not change which of them are visible.
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
     * The fields the template puts in the header, in the order the pages and
     * the sections put them.
     *
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
     * The facts panel down the right: the nickname, which the product imposes
     * and therefore always knows about, and every highlighted field the header
     * had no room for.
     *
     * It used to list six questions the product would always ask. It asks none
     * of them: what is asked about somebody is what their template asks, so the
     * panel reads the template rather than a list written here.
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
     * What the history holds. Placeholder until activities exist: the one thing
     * the row does know is when the person was written down, so that is the
     * single entry, and it is true.
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
     * The sentence in each of the panels that has no model behind it. They are
     * drawn rather than left out, because the shape of the page is the point of
     * it, and each says what will land there rather than merely that it is
     * empty.
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
     * One section's fields, the ones that hold a value. A divider or a heading
     * is there to break a long form up and carries nothing, so a page showing
     * what is known about somebody has nothing to say about it.
     *
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
     * Every repetition on the record, grouped by the repeater it answers, read
     * once for the whole screen for the same reason the plain values are: a page
     * holding three repeaters of four questions each would otherwise ask the
     * database about every box on it.
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
     * What one repeater holds, in the order somebody put it in: a list of
     * repetitions, each carrying the answers to the questions the repeater asks.
     *
     * A field that is not a repeater holds no repetitions rather than an empty
     * list of them, and both read as the same empty array here, because what the
     * panel needs to know is only whether there is anything to draw.
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

    /**
     * How many fields somebody would find on a page. It counts what the page
     * would ask, so the layout types are left out here for the same reason they
     * are left out of the page itself.
     */
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
