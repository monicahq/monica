<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Field;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Settings\Account\Templates\FieldCatalogueViewModel;
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
 * Half of this screen has no model behind it yet. Nothing stores a value
 * against a field, so every field reads as not recorded rather than showing
 * something invented that cannot then be edited. The history, the facts, the
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
    ) {}

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
     * The template the page renders from, and which version of it. Both are
     * real, and both matter: two people on the same template can be on
     * different versions of it, and that is what explains why one of them has a
     * page the other has not.
     */
    public function templateName(): string
    {
        return $this->contact->templateVersion->template->name;
    }

    public function templateVersionLabel(): string
    {
        return 'v'.$this->contact->templateVersion->version_number;
    }

    /**
     * Where the pages of this record are decided. It is the builder, which is
     * an account screen rather than a vault one: a template is shared by every
     * vault, so changing it here changes it for everybody on it.
     */
    public function templateUrl(): string
    {
        return route('settings.templateBuilder.index', $this->contact->templateVersion->template);
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
     * Every value is empty. A field's value is its own table and it has not
     * been written, so what the page can honestly say is which questions it
     * asks about somebody, and that none of them has been answered yet.
     *
     * @return array<int, array{name: string, iconClass: string, icon: string, fields: array<int, array{label: string, help: string|null, typeLabel: string, value: string|null}>}>
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
            ->map(fn (TemplateVersionSection $row, int $index): array => [
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                'icon' => $row->sectionVersion->section->icon ?? 'note',
                'iconClass' => self::SECTION_HUES[$index % count(self::SECTION_HUES)],
                'fields' => $this->fieldsIn($row),
            ])
            ->all();
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
     * The three facts beside the photograph. Placeholder until dates, addresses
     * and activities exist. Only the last of them is real today.
     *
     * @return array<int, array{icon: string, label: string, value: string, iconClass: string}>
     */
    public function headerFacts(): array
    {
        return [
            [
                'icon' => 'calendar',
                'label' => __('Birthday'),
                'value' => __('Not recorded'),
                'iconClass' => 'text-rose',
            ],
            [
                'icon' => 'place',
                'label' => __('Lives in'),
                'value' => __('Not recorded'),
                'iconClass' => 'text-forest',
            ],
            [
                'icon' => 'timeline',
                'label' => __('Last in touch'),
                'value' => $this->contact->last_activity_at?->diffForHumans() ?? __('never'),
                'iconClass' => 'text-muted',
            ],
        ];
    }

    /**
     * The facts panel down the right. Placeholder until a value is stored
     * against a field: these are the questions the product will always ask
     * about somebody, and none of them can be answered yet.
     *
     * @return array<int, array{icon: string, label: string, value: string|null}>
     */
    public function facts(): array
    {
        return [
            ['icon' => 'calendar', 'label' => __('Born'), 'value' => null],
            ['icon' => 'place', 'label' => __('Address'), 'value' => null],
            ['icon' => 'device', 'label' => __('Telephone'), 'value' => null],
            ['icon' => 'mail', 'label' => __('Email'), 'value' => null],
            ['icon' => 'info', 'label' => __('How you met'), 'value' => null],
            ['icon' => 'file', 'label' => __('Nickname'), 'value' => $this->contact->nickname],
        ];
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
     * @return array<int, array{label: string, help: string|null, typeLabel: string, value: string|null}>
     */
    private function fieldsIn(TemplateVersionSection $row): array
    {
        return $this->storedFields($row->sectionVersion->fields)
            ->map(fn (Field $field): array => [
                'label' => $field->label,
                'help' => $field->help_text,
                'typeLabel' => FieldCatalogueViewModel::labelFor($field->type),
                'value' => null,
            ])
            ->values()
            ->all();
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
