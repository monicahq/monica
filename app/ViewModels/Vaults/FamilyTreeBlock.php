<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

class FamilyTreeBlock
{
    public function __construct(
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    /** @return array<int, array{name: string, url: string|null, avatarUrl: string|null, isNameOnly: bool}> */
    public function parents(): array
    {
        return once(fn (): array => $this->nodes(
            Contact::query()->parentsOf($this->contact)->with('avatar')->orderBy('sort_key')->get()
        ));
    }

    /** @return array<int, array{name: string, url: string|null, avatarUrl: string|null, isNameOnly: bool}> */
    public function currentPartners(): array
    {
        return once(fn (): array => $this->nodes(
            Contact::query()->currentPartnersOf($this->contact)->with('avatar')->orderBy('sort_key')->get()
        ));
    }

    /** @return array<int, array{name: string, url: string|null, avatarUrl: string|null, isNameOnly: bool}> */
    public function children(): array
    {
        return once(fn (): array => $this->nodes(
            Contact::query()->childrenOf($this->contact)->with('avatar')->orderBy('sort_key')->get()
        ));
    }

    /**
     * The contact the page is about, drawn in the middle tier.
     *
     * It leads nowhere: the reader is already on this person's page.
     *
     * @return array{name: string, url: null, avatarUrl: string|null, isNameOnly: bool}
     */
    public function subject(): array
    {
        return [
            'name' => $this->contact->name,
            'url' => null,
            'avatarUrl' => $this->avatarUrl($this->contact),
            'isNameOnly' => (bool) $this->contact->is_partial,
        ];
    }

    public function holdsNothing(): bool
    {
        return $this->parents() === [] && $this->currentPartners() === [] && $this->children() === [];
    }

    public function scopeLine(): string
    {
        return __('Only parentage and partnerships are drawn here. Friends and colleagues stay in the list.');
    }

    public function nameOnlyLine(): string
    {
        return __('Name only, no profile yet');
    }

    public function emptyLine(): string
    {
        return __('Nobody is written down as :name’s parent, partner or child yet, so there is no line to draw.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
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
     * them holds their name and nothing else.
     *
     * @param  Collection<int, Contact>  $people
     * @return array<int, array{name: string, url: string|null, avatarUrl: string|null, isNameOnly: bool}>
     */
    private function nodes(Collection $people): array
    {
        return $people
            ->map(fn (Contact $person): array => [
                'name' => $person->name,
                'url' => $person->is_partial
                    ? null
                    : route('contacts.show', ['vault' => $this->vault, 'contact' => $person]),
                'avatarUrl' => $this->avatarUrl($person),
                'isNameOnly' => (bool) $person->is_partial,
            ])
            ->values()
            ->all();
    }

    private function avatarUrl(Contact $person): ?string
    {
        if ($person->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->vault, 'file' => $person->avatar]);
    }
}
