<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
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
        return once(fn (): array => $this->rows(
            Contact::query()->childrenOf($this->contact)->with('avatar')->orderBy('sort_key')->get()
        ));
    }

    /** @return array<int, array{name: string, url: string|null, avatarUrl: string|null, age: int|null, note: string|null}> */
    public function currentPartners(): array
    {
        return once(fn (): array => $this->rows(
            Contact::query()->currentPartnersOf($this->contact)->with('avatar')->orderBy('sort_key')->get()
        ));
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

    public function emptyLine(): string
    {
        return __(':name’s children and current partner land here, once you write down who they are. This says nothing about the family :name has.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
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
        $ages = ContactAges::of($people->modelKeys());

        return $people
            ->map(fn (Contact $person): array => [
                'name' => $person->name,
                'url' => $person->is_partial
                    ? null
                    : route('contacts.show', ['vault' => $this->vault, 'contact' => $person]),
                'avatarUrl' => $person->avatar === null
                    ? null
                    : route('files.show', ['vault' => $this->vault, 'file' => $person->avatar]),
                'age' => $person->is_partial ? null : ($ages[$person->id] ?? null),
                'note' => $person->is_partial ? __('Name only, no profile yet') : null,
            ])
            ->values()
            ->all();
    }
}
