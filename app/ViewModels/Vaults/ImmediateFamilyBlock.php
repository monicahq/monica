<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\RelationshipBehaviorEnum;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

class ImmediateFamilyBlock
{
    public function __construct(
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    /** @return array<int, array{name: string, url: string|null, avatarUrl: string|null, age: int|null, note: string|null}> */
    public function children(): array
    {
        return once(fn (): array => $this->rows($this->childContacts()));
    }

    /** @return array<int, array{name: string, url: string|null, avatarUrl: string|null, age: int|null, note: string|null}> */
    public function currentPartners(): array
    {
        return once(fn (): array => $this->rows($this->partnerContacts()));
    }

    public function childCount(): int
    {
        return count($this->children());
    }

    public function currentPartnerCount(): int
    {
        return count($this->currentPartners());
    }

    public function holdsNothing(): bool
    {
        return $this->children() === [] && $this->currentPartners() === [];
    }

    public function contactName(): string
    {
        return $this->contact->first_name ?? $this->contact->name;
    }

    public function contactFullName(): string
    {
        return $this->contact->name;
    }

    public function contactAvatarUrl(): ?string
    {
        if ($this->contact->avatar === null) {
            return null;
        }

        return route('files.variants.show', ['vault' => $this->vault, 'file' => $this->contact->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]);
    }

    public function addPartnerUrl(): string
    {
        return $this->addRelationshipUrlFor(RelationshipBehaviorEnum::Romantic);
    }

    public function addChildUrl(): string
    {
        return $this->addRelationshipUrlFor(RelationshipBehaviorEnum::GenealogyParentage);
    }

    public function addParentUrl(): string
    {
        return $this->addRelationshipUrlFor(RelationshipBehaviorEnum::GenealogyParentage, swapped: true);
    }

    public function noChildrenLine(): string
    {
        return __('No children recorded');
    }

    public function noPartnerLine(): string
    {
        return __('No current partner recorded');
    }

    public function addRelationshipUrl(): string
    {
        return route('contacts.relationships.new', [
            'vault' => $this->vault,
            'contact' => $this->contact,
        ]);
    }

    /**
     * The form opens on the first type the account still offers with that behaviour. The person on
     * screen stands on side A, which is the parent side of Parent / Child, so a child needs no swap
     * and a parent needs one.
     */
    private function addRelationshipUrlFor(RelationshipBehaviorEnum $behavior, bool $swapped = false): string
    {
        $type = RelationshipType::query()
            ->where('account_id', $this->contact->account_id)
            ->where('system_behavior', $behavior)
            ->where('is_active', true)
            ->orderBy('position')
            ->first();

        return route('contacts.relationships.new', array_filter([
            'vault' => $this->vault,
            'contact' => $this->contact,
            'type' => $type?->id,
            'swapped' => $type !== null && $swapped ? 1 : null,
        ]));
    }

    /**
     * Somebody written down while a relationship was being entered, and never
     * filled in, is named but not offered as a way through. The record behind
     * them holds their name and nothing else, so a link into it leads nowhere
     * worth going.
     *
     * @param  Collection<int, Contact>  $people
     * @return array<int, array{name: string, url: string|null, avatarUrl: string|null, age: int|null, note: string|null}>
     */
    private function rows(Collection $people): array
    {
        $ages = $this->ages();

        return $people
            ->map(fn (Contact $person): array => [
                'name' => $person->name,
                'url' => $person->is_partial
                    ? null
                    : route('contacts.show', ['vault' => $this->vault, 'contact' => $person]),
                'avatarUrl' => $person->avatar === null
                    ? null
                    : route('files.variants.show', ['vault' => $this->vault, 'file' => $person->avatar, 'size' => Contact::AVATAR_THUMBNAIL_PIXELS]),
                'age' => $person->is_partial ? null : ($ages[$person->id] ?? null),
                'note' => $person->is_partial ? __('Name only, no profile yet') : null,
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, Contact> */
    private function childContacts(): Collection
    {
        return once(fn (): Collection => Contact::query()->childrenOf($this->contact)->with('avatar')->orderBy('sort_key')->get());
    }

    /** @return Collection<int, Contact> */
    private function partnerContacts(): Collection
    {
        return once(fn (): Collection => Contact::query()->currentPartnersOf($this->contact)->with('avatar')->orderBy('sort_key')->get());
    }

    /** @return array<int, int> */
    private function ages(): array
    {
        return once(fn (): array => ContactAges::of([
            ...$this->childContacts()->modelKeys(),
            ...$this->partnerContacts()->modelKeys(),
        ]));
    }
}
