<?php

declare(strict_types=1);

namespace App\ViewModels\Shared;

use App\Enums\VaultColourEnum;
use App\Helpers\FieldValueFormatter;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Section;
use App\Models\ShareLink;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Somebody's page as whoever holds a link sees it.
 *
 * This is where the exclusions on a link stop being a stored intention and start
 * being what a page does or does not say. Everything is held back unless the link
 * lets it through, and the reading happens in one place so no screen can let
 * something slip by asking a different question.
 *
 * The rules the specification is careful about, in the order they bite:
 *
 * - A section the link excludes is not drawn, and its fields are never read.
 * - A page whose every section is excluded is not a tab. An empty tab would tell
 *   the recipient that something is being kept from them, which is a leak of a
 *   kind even though it carries no value.
 * - One page left means no tab list at all, the way the private record hides its
 *   rail when there is nothing to choose between.
 * - A field marked sensitive is dropped unless the link says otherwise, inside a
 *   shared section as much as anywhere else.
 * - A field holding a file is dropped always. Handing over the file would need a
 *   public route onto it with the link's authority, and this page has none.
 *
 * Values are formatted the way whoever made the link writes dates and numbers.
 * The page is a document they shared, not a screen belonging to the person
 * reading it, and there is no reader to have a preference anyway.
 */
class SharedContactViewModel
{
    public function __construct(
        private readonly ShareLink $shareLink,
        private readonly Contact $contact,
        private readonly string $token,
        private readonly ?string $selectedPageKey = null,
    ) {}

    public function shareLink(): ShareLink
    {
        return $this->shareLink;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return $this->contact->name;
    }

    /**
     * Who shared it, which is the one thing the recipient needs in order to know
     * whom to tell when something on the page is wrong.
     */
    public function sharedBy(): string
    {
        $maker = $this->shareLink->createdBy;

        if ($maker === null) {
            return __('Shared with you');
        }

        return __('Shared with you by :name', ['name' => $maker->name]);
    }

    /**
     * The chip in the bar, which says the two things a recipient wonders about:
     * whether they can change anything, and how long this lasts.
     */
    public function terms(): string
    {
        if ($this->shareLink->expires_at === null) {
            return __('Read only');
        }

        return __('Read only · ends :date', ['date' => $this->shareLink->expires_at->isoFormat('LL')]);
    }

    public function closingLine(): string
    {
        $maker = $this->shareLink->createdBy;

        if ($maker === null) {
            return __('This is a page from somebody’s private records, shared with you as it stands today. There is nothing to sign in to and nothing to reply to here.');
        }

        return __('This is a page from somebody’s private records, shared with you as it stands today. There is nothing to sign in to and nothing to reply to here. If something is wrong or missing, tell :name.', [
            'name' => $maker->first_name ?? $maker->name,
        ]);
    }

    /**
     * The facts under the name: the highlighted fields of the sections that were
     * shared, which is what the private record puts beside the photograph.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function headerFacts(): array
    {
        return $this->readableFields()
            ->filter(fn (Field $field): bool => $field->is_highlighted)
            ->map(fn (Field $field): array => [
                'label' => $field->label,
                'value' => $this->valueOf($field),
            ])
            ->filter(fn (array $fact): bool => $fact['value'] !== '')
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * The tabs, or an empty list when there is nothing to choose between.
     *
     * @return array<int, array{key: string, label: string, url: string, isSelected: bool}>
     */
    public function tabs(): array
    {
        $pages = $this->shownPages();

        if ($pages->count() < 2) {
            return [];
        }

        return $pages
            ->map(fn (TemplateVersionPage $page): array => [
                'key' => $page->key,
                'label' => $page->label,
                'url' => route('share.show', ['token' => $this->token, 'page' => $page->key]),
                'isSelected' => $page->is($this->selectedPage()),
            ])
            ->values()
            ->all();
    }

    /**
     * The sections of the open page, each with the fields that hold something.
     * A section every field of which was dropped is not drawn either: a titled
     * panel with nothing in it says as little as an empty tab and raises the same
     * question.
     *
     * @return array<int, array{name: string, icon: string, tint: string, fields: array<int, array{label: string, value: string}>}>
     */
    public function sections(): array
    {
        $page = $this->selectedPage();

        if ($page === null) {
            return [];
        }

        return $this->includedSectionsOn($page)
            ->map(fn (TemplateVersionSection $row): array => [
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
                /* The mark is display only and says nothing about the record,
                   so a private link carries it: a reader sees the section the
                   way the account does. */
                'icon' => in_array($row->sectionVersion->section->icon, Section::ICONS, true)
                    ? $row->sectionVersion->section->icon
                    : Section::DEFAULT_ICON,
                'tint' => VaultColourEnum::tintFor($row->sectionVersion->section->colour),
                'fields' => $this->fieldsIn($row),
            ])
            ->filter(fn (array $section): bool => $section['fields'] !== [])
            ->values()
            ->all();
    }

    public function emptyLine(): string
    {
        return __('Nothing on this page has been filled in yet.');
    }

    /**
     * @return Collection<int, TemplateVersionPage>
     */
    private function shownPages(): Collection
    {
        return once(fn (): Collection => $this->contact->templateVersion->pages
            ->filter(fn (TemplateVersionPage $page): bool => $this->includedSectionsOn($page)->isNotEmpty())
            ->values());
    }

    /**
     * A page named by an address this link does not open falls back to the first
     * one it does, rather than refusing: a tab that is not shared is not an error
     * worth a page of its own, and answering differently would say which pages
     * exist.
     */
    private function selectedPage(): ?TemplateVersionPage
    {
        return once(function (): ?TemplateVersionPage {
            $pages = $this->shownPages();

            return $pages->firstWhere('key', $this->selectedPageKey) ?? $pages->first();
        });
    }

    /**
     * @return Collection<int, TemplateVersionSection>
     */
    private function includedSectionsOn(TemplateVersionPage $page): Collection
    {
        $excluded = $this->shareLink->excluded_section_ids;

        return $this->contact->templateVersion->assembly
            ->where('template_version_page_id', $page->id)
            ->reject(fn (TemplateVersionSection $row): bool => in_array($row->id, $excluded, true))
            ->values();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function fieldsIn(TemplateVersionSection $row): array
    {
        return $this->readableIn($row)
            ->map(fn (Field $field): array => [
                'label' => $field->label,
                'value' => $this->valueOf($field),
            ])
            ->filter(fn (array $field): bool => $field['value'] !== '')
            ->values()
            ->all();
    }

    /**
     * Every field of every shared section, which is what the header reads across
     * pages the way the private record does.
     *
     * @return Collection<int, Field>
     */
    private function readableFields(): Collection
    {
        return once(fn (): Collection => $this->shownPages()
            ->flatMap(fn (TemplateVersionPage $page): Collection => $this->includedSectionsOn($page))
            ->flatMap(fn (TemplateVersionSection $row): Collection => $this->readableIn($row))
            ->values());
    }

    /**
     * The fields of one section this link is allowed to show. A repeater is left
     * out with the files: what it holds lives on rows of its own, and reading
     * those honestly is work this page does not do yet.
     *
     * @return Collection<int, Field>
     */
    private function readableIn(TemplateVersionSection $row): Collection
    {
        return $row->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->storesValue())
            ->reject(fn (Field $field): bool => $field->type->holdsAFile() || $field->type->isRepeater())
            ->reject(fn (Field $field): bool => $field->is_sensitive && ! $this->shareLink->includes_sensitive_fields)
            ->values();
    }

    private function valueOf(Field $field): string
    {
        $value = $this->values()->get($field->id);

        if ($value === null) {
            return '';
        }

        return $this->formatter()?->format($value) ?? '';
    }

    /**
     * @return Collection<int, FieldValue>
     */
    private function values(): Collection
    {
        return once(fn (): Collection => $this->contact
            ->fieldValues()
            ->attached()
            ->whereNull('field_value_row_id')
            ->with(['field', 'valueListItem'])
            ->get()
            ->keyBy('field_id')
            ->toBase());
    }

    /**
     * Formatted the way whoever made the link reads dates and numbers. When that
     * user has since been removed, the account's own falls back in: what is being
     * chosen is a date order, and the account has one way of writing them.
     */
    private function formatter(): ?FieldValueFormatter
    {
        return once(function (): ?FieldValueFormatter {
            $user = $this->shareLink->createdBy ?? $this->shareLink->account->user;

            return $user instanceof User ? new FieldValueFormatter($user) : null;
        });
    }
}
