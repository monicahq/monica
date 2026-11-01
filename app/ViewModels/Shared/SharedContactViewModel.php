<?php

declare(strict_types=1);

namespace App\ViewModels\Shared;

use App\Enums\VaultColourEnum;
use App\Helpers\FieldValueFormatter;
use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\GroupMember;
use App\Models\Section;
use App\Models\ShareLink;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    public function sharedBy(): string
    {
        $maker = $this->shareLink->createdBy;

        if ($maker === null) {
            return __('Shared with you');
        }

        return __('Shared with you by :name', ['name' => $maker->name]);
    }

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
     * The names of the groups the contact is in today, when the link was set to show them. The
     * other people in those groups are never shown.
     *
     * @return list<string>
     */
    public function groups(): array
    {
        if (! $this->shareLink->includes_groups || ! FlagState::isOn('groups', $this->contact->account)) {
            return [];
        }

        return GroupMember::query()
            ->where('contact_id', $this->contact->id)
            ->whereNull('left_on')
            ->whereHas('group', fn (Builder $query): Builder => $query->where('vault_id', $this->contact->vault_id))
            ->with('group')
            ->get()
            ->map(fn (GroupMember $membership): string => $membership->group->name)
            ->sort()
            ->values()
            ->all();
    }

    /** @return array<int, array{label: string, value: string}> */
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

    /** @return array<int, array{key: string, label: string, url: string, isSelected: bool}> */
    public function tabs(): array
    {
        $pages = $this->shownPages();

        if ($pages->count() < 2) {
            return [];
        }

        return $pages
            ->map(fn (StructureVersionPage $page): array => [
                'key' => $page->key,
                'label' => $page->label,
                'url' => route('share.show', ['token' => $this->token, 'page' => $page->key]),
                'isSelected' => $page->is($this->selectedPage()),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, icon: string, tint: string, fields: array<int, array{label: string, value: string}>}>
     */
    public function sections(): array
    {
        $page = $this->selectedPage();

        if ($page === null) {
            return [];
        }

        return $this->includedSectionsOn($page)
            ->map(fn (StructureVersionSection $row): array => [
                'name' => $row->title_override ?? $row->sectionVersion->section->name,
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

    /** @return Collection<int, StructureVersionPage> */
    private function shownPages(): Collection
    {
        return once(fn (): Collection => $this->contact->structureVersion->pages
            ->filter(fn (StructureVersionPage $page): bool => $this->includedSectionsOn($page)->isNotEmpty())
            ->values());
    }

    private function selectedPage(): ?StructureVersionPage
    {
        return once(function (): ?StructureVersionPage {
            $pages = $this->shownPages();

            return $pages->firstWhere('key', $this->selectedPageKey) ?? $pages->first();
        });
    }

    /** @return Collection<int, StructureVersionSection> */
    private function includedSectionsOn(StructureVersionPage $page): Collection
    {
        $excluded = $this->shareLink->excluded_section_ids;

        return $this->contact->structureVersion->assembly
            ->where('structure_version_page_id', $page->id)
            ->reject(fn (StructureVersionSection $row): bool => in_array($row->id, $excluded, true))
            ->values();
    }

    /** @return array<int, array{label: string, value: string}> */
    private function fieldsIn(StructureVersionSection $row): array
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

    /** @return Collection<int, Field> */
    private function readableFields(): Collection
    {
        return once(fn (): Collection => $this->shownPages()
            ->flatMap(fn (StructureVersionPage $page): Collection => $this->includedSectionsOn($page))
            ->flatMap(fn (StructureVersionSection $row): Collection => $this->readableIn($row))
            ->values());
    }

    /** @return Collection<int, Field> */
    private function readableIn(StructureVersionSection $row): Collection
    {
        return $row->sectionVersion->fields
            ->whereNull('parent_field_id')
            ->filter(fn (Field $field): bool => $field->type->storesValue())
            ->reject(fn (Field $field): bool => $field->type->holdsAFile() || $field->type->isRepeater() || $field->type->isRelation())
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

    /** @return Collection<int, FieldValue> */
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

    private function formatter(): ?FieldValueFormatter
    {
        return once(function (): ?FieldValueFormatter {
            $user = $this->shareLink->createdBy ?? $this->shareLink->account->user;

            return $user instanceof User ? new FieldValueFormatter($user) : null;
        });
    }
}
