<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\StructureAppliesToEnum;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ContactFormViewModel
{
    /** @var Collection<int, Template>|null */
    private ?Collection $contactTemplates = null;

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
        return __('New contact');
    }

    public function action(): string
    {
        return route('contacts.create', $this->vault);
    }

    /**
     * What the free offer allows, said only when the account is at it. The form still opens and
     * still edits: it is an addition that is refused, never what is already there.
     */
    public function ceilingMessage(): ?string
    {
        $response = $this->user->inspect('create', Contact::class);

        return $response->denied() ? (string) $response->message() : null;
    }

    public function contactsUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    /**
     * @return array{nickname: string, maidenName: string, structureVersionId: string, isFavourite: bool, isPartial: bool, keepGoing: bool}
     */
    public function values(): array
    {
        return [
            'nickname' => (string) old('nickname', ''),
            'maidenName' => (string) old('maiden_name', ''),
            'structureVersionId' => (string) old('structure_version_id', $this->defaultStructureVersionId()),
            'isFavourite' => $this->ticked('is_favourite'),
            'isPartial' => $this->ticked('is_partial'),
            'keepGoing' => $this->ticked('keep_going'),
        ];
    }

    public function nameEditor(): ContactNameEditorBlock
    {
        return new ContactNameEditorBlock;
    }

    /**
     * The templates a contact can start from. The template the vault uses is offered at the version
     * the vault names, which is not always its latest, and every other one at its latest version.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function templates(): array
    {
        $vaultVersion = $this->vaultStructureVersion();

        return $this->contactTemplates()
            ->map(fn (Template $template): array => [
                'id' => $vaultVersion?->structure_id === $template->structure->id
                    ? $vaultVersion->id
                    : (int) $template->structure->published_version_id,
                'name' => $template->name,
            ])
            ->all();
    }

    public function showsTemplates(): bool
    {
        return count($this->templates()) > 1;
    }

    /** @return array<int, array{id: string, name: string, help: string, checked: bool}> */
    public function flags(): array
    {
        $values = $this->values();

        return [
            [
                'id' => 'is_favourite',
                'name' => __('Favourite'),
                'help' => __('Kept at the top of the contact list.'),
                'checked' => $values['isFavourite'],
            ],
            [
                'id' => 'is_partial',
                'name' => __('Partial contact'),
                'help' => __('This person won’t have a contact page or appear in search results or your contact list. You can turn them into a full contact later'),
                'checked' => $values['isPartial'],
            ],
        ];
    }

    public function blocker(): ?string
    {
        if ($this->canCreate()) {
            return null;
        }

        return __('This vault has no template for new contacts to start from. Give it one in the vault settings, and this form will work.');
    }

    public function canCreate(): bool
    {
        return $this->startingVersion() !== null;
    }

    private function startingVersion(): ?StructureVersion
    {
        return once(function (): ?StructureVersion {
            $chosen = (int) $this->values()['structureVersionId'];

            if ($chosen === 0) {
                return null;
            }

            return StructureVersion::query()
                ->where('account_id', $this->user->account_id)
                ->find($chosen);
        });
    }

    private function defaultStructureVersionId(): string
    {
        return once(function (): string {
            $version = VaultDefaultTemplate::query()
                ->where('vault_id', $this->vault->id)
                ->where('applies_to', StructureAppliesToEnum::Contact)
                ->value('structure_version_id');

            return $version === null ? '' : (string) $version;
        });
    }

    private function vaultStructureVersion(): ?StructureVersion
    {
        return once(function (): ?StructureVersion {
            $id = $this->defaultStructureVersionId();

            return $id === '' ? null : StructureVersion::query()->find((int) $id);
        });
    }

    private function ticked(string $field): bool
    {
        if (! session()->hasOldInput()) {
            return false;
        }

        return session()->hasOldInput($field);
    }

    /** @return Collection<int, Template> */
    private function contactTemplates(): Collection
    {
        return $this->contactTemplates ??= Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_active', true)
            ->whereHas('structure', function (Builder $query): void {
                $query->where('applies_to', StructureAppliesToEnum::Contact)
                    ->whereNotNull('published_version_id');
            })
            ->with('structure')
            ->orderBy('position')
            ->get();
    }
}
