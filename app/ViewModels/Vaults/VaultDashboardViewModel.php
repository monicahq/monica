<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\StructureAppliesToEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;

class VaultDashboardViewModel
{
    private const int PEOPLE_SHOWN = 5;

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
        return $this->vault->name;
    }

    public function description(): ?string
    {
        return $this->vault->description;
    }

    public function vaultsUrl(): string
    {
        return route('vaults.index');
    }

    public function newContactUrl(): string
    {
        return route('contacts.new', $this->vault);
    }

    /**
     * What the free offer allows, said only when the account is at it. The control stays on the
     * screen and says why, rather than disappearing and leaving nothing to understand.
     */
    public function ceilingMessage(): ?string
    {
        $response = $this->user->inspect('create', Contact::class);

        return $response->denied() ? (string) $response->message() : null;
    }

    public function allContactsUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function settingsUrl(): string
    {
        return route('vaults.settings.index', $this->vault);
    }

    /** @return array<int, array{name: string, nickname: string, avatarUrl: string|null, url: string}> */
    public function people(): array
    {
        return $this->vault
            ->contacts()
            ->listed()
            ->with('avatar')
            ->orderByDesc('created_at')
            ->limit(self::PEOPLE_SHOWN)
            ->get()
            ->map(fn (Contact $contact): array => [
                'name' => $contact->name,
                'nickname' => (string) $contact->nickname,
                'avatarUrl' => $contact->avatar === null
                    ? null
                    : route('files.show', ['vault' => $this->vault, 'file' => $contact->avatar]),
                'url' => route('contacts.show', ['vault' => $this->vault, 'contact' => $contact]),
            ])
            ->all();
    }

    public function peopleCaption(): string
    {
        return $this->contactCount();
    }

    public function allPeopleLabel(): string
    {
        if ($this->vault->contact_count <= self::PEOPLE_SHOWN) {
            return __('The whole list');
        }

        return trans_choice(':count person|All :count people', $this->vault->contact_count);
    }

    /** @return array<int, array{title: string, line: string}> */
    public function settingsRows(): array
    {
        return [
            [
                'title' => __('What a new contact starts from'),
                'line' => $this->currentTemplateName() ?? __('Nothing is set, so whoever writes somebody down picks a template themselves.'),
            ],
            [
                'title' => __('Delete this vault'),
                'line' => $this->deletionConsequence(),
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, caption: string, icon: string, emptyTitle: string, emptyText?: string}>
     */
    public function unbuiltSections(): array
    {
        return [
            [
                'title' => __('Tasks'),
                'caption' => __('Nothing open'),
                'icon' => 'tasks',
                'emptyTitle' => __('Nothing you owe anyone'),
            ],
            [
                'title' => __('Reminders'),
                'caption' => __('No dates set'),
                'icon' => 'reminder',
                'emptyTitle' => __('No dates to keep'),
                'emptyText' => __('Birthdays and anything else you ask to be told about, in good time.'),
            ],
            [
                'title' => __('Files'),
                'caption' => __('No files'),
                'icon' => 'file',
                'emptyTitle' => __('No files yet'),
                'emptyText' => __('Photographs, scans and documents you attach to somebody appear here as well.'),
            ],
        ];
    }

    private function contactCount(): string
    {
        if ($this->vault->contact_count === 0) {
            return __('Nobody in it yet');
        }

        return trans_choice(':count person|:count people', $this->vault->contact_count);
    }

    private function deletionConsequence(): string
    {
        if ($this->vault->contact_count === 0) {
            return __('Takes the vault and everything in it.');
        }

        return trans_choice('{1}Deletes the one person in it and everything written about them.|Deletes :count people and everything written about them.', $this->vault->contact_count);
    }

    private function currentTemplateName(): ?string
    {
        return VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->first()
            ?->structureVersion
            ->structure
            ->owner
            ->name;
    }
}
