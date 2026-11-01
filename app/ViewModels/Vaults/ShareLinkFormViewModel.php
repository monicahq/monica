<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ShareLinkExpiryEnum;
use App\Helpers\FlagState;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;

class ShareLinkFormViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?ShareLink $shareLink = null,
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

    public function shareLink(): ?ShareLink
    {
        return $this->shareLink;
    }

    public function isEditing(): bool
    {
        return $this->shareLink !== null;
    }

    public function title(): string
    {
        if ($this->shareLink !== null) {
            return __('Change what :label shows', ['label' => $this->shareLink->label]);
        }

        return __('Share :name', ['name' => $this->contact->name]);
    }

    public function breadcrumbLabel(): string
    {
        return $this->shareLink !== null ? $this->shareLink->label : __('Share');
    }

    public function intro(): string
    {
        return __('A private link to this one page. Whoever opens it needs no account, joins nothing, and can change nothing. Everything sensitive starts out hidden, and you decide what to show.');
    }

    public function neverLine(): string
    {
        return __('Write anything, reach anything outside this page, or become an account. A field you marked sensitive stays hidden even inside a section you shared. Anything attached as a file stays here. Notifications never reach whoever holds the link. If you delete :name, the link stops working.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function templateLine(): string
    {
        $version = $this->contact->structureVersion;

        return __(':template, version :number', [
            'template' => $version->structure->owner->name,
            'number' => $version->version_number,
        ]);
    }

    public function avatarUrl(): ?string
    {
        if ($this->contact->avatar === null) {
            return null;
        }

        return route('files.variants.show', ['vault' => $this->vault, 'file' => $this->contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]);
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function linksUrl(): string
    {
        return route('contacts.shareLinks.index', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function action(): string
    {
        if ($this->shareLink !== null) {
            return route('contacts.shareLinks.update', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'shareLink' => $this->shareLink,
            ]);
        }

        return route('contacts.shareLinks.create', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function method(): string
    {
        return $this->shareLink === null ? 'post' : 'put';
    }

    public function submitLabel(): string
    {
        return $this->shareLink === null ? __('Create sharing link') : __('Save what it shows');
    }

    public function submitLine(): string
    {
        if ($this->shareLink !== null) {
            return __('Whoever holds the link sees the change on their next click.');
        }

        return __('The link is made when you press this. You can change what it shows afterwards, or revoke it.');
    }

    public function label(): ?string
    {
        return $this->shareLink?->label;
    }

    public function recipientHint(): ?string
    {
        return $this->shareLink?->recipient_hint;
    }

    public function includesSensitiveFields(): bool
    {
        return $this->shareLink !== null && $this->shareLink->includes_sensitive_fields;
    }

    /**
     * Whether the form offers to show the groups the contact belongs to, which it does only once
     * groups are released to the account.
     */
    public function offersGroups(): bool
    {
        return FlagState::isOn('groups', $this->user->account);
    }

    public function includesGroups(): bool
    {
        return $this->shareLink !== null && $this->shareLink->includes_groups;
    }

    public function isProtectedByAPassword(): bool
    {
        return $this->shareLink?->isProtectedByAPassword() ?? false;
    }

    /** @return array<int, array{value: string, label: string, help: string, isSelected: bool}> */
    public function expiryOptions(): array
    {
        $keeps = $this->shareLink !== null && $this->shareLink->expires_at !== null;
        $options = [];

        if ($keeps) {
            $options[] = [
                'value' => 'keep',
                'label' => __('Leave it at :date', ['date' => $this->shareLink->expires_at->isoFormat('LL')]),
                'help' => __('After that the address stops working on its own.'),
                'isSelected' => true,
            ];
        }

        foreach (ShareLinkExpiryEnum::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => __($case->label()),
                'help' => __($case->help()),
                'isSelected' => ! $keeps && $case === ShareLinkExpiryEnum::ThirtyDays,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{key: string, label: string, sections: array<int, array{id: int, name: string, isIncluded: bool}>}>
     */
    public function pages(): array
    {
        $excluded = $this->shareLink === null ? [] : $this->shareLink->excluded_section_ids;

        return $this->contact->structureVersion->pages
            ->map(fn (StructureVersionPage $page): array => [
                'key' => $page->key,
                'label' => $page->label,
                'sections' => $this->contact->structureVersion->assembly
                    ->where('structure_version_page_id', $page->id)
                    ->map(fn (StructureVersionSection $row): array => [
                        'id' => $row->id,
                        'name' => $row->title_override ?? $row->sectionVersion->section->name,
                        'isIncluded' => ! in_array($row->id, $excluded, true),
                    ])
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $page): bool => $page['sections'] !== [])
            ->values()
            ->all();
    }

    public function sectionCount(): int
    {
        return $this->contact->structureVersion->assembly->count();
    }

    public function sectionsLine(): string
    {
        return __('Their page is laid out by :template. Hide a section and it is not shown. A page whose sections are all hidden does not appear as an empty tab.', [
            'template' => $this->templateLine(),
        ]);
    }
}
