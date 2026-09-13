<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\ContactGroupingEnum;
use App\Enums\RelationshipStatusEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

class RelationshipsBlock
{
    public function __construct(
        private readonly Vault $vault,
        private readonly Contact $contact,
    ) {}

    /**
     * Every link the contact has, gathered under the category its nature belongs to.
     *
     * A category with nothing in it is dropped rather than drawn empty, and the
     * categories that remain keep the order the account put them in.
     *
     * @return array<int, array{label: string, count: int, rows: array<int, array<string, mixed>>}>
     */
    public function groups(): array
    {
        return once(function (): array {
            $relationships = $this->relationships();
            $ages = ContactAges::of($relationships->map(fn (Relationship $r): int => $this->other($r)->id)->all());

            $groups = [];

            foreach ($relationships as $relationship) {
                $category = $relationship->relationshipType->relationshipCategory;

                $groups[$category->id]['label'] = $category->name;
                $groups[$category->id]['position'] = $category->position;
                $groups[$category->id]['rows'][] = $this->row($relationship, $ages);
            }

            usort($groups, fn (array $one, array $other): int => $one['position'] <=> $other['position']);

            return array_map(fn (array $group): array => [
                'label' => $group['label'],
                'count' => count($group['rows']),
                'rows' => $group['rows'],
            ], $groups);
        });
    }

    public function holdsNothing(): bool
    {
        return $this->groups() === [];
    }

    public function total(): int
    {
        return array_sum(array_column($this->groups(), 'count'));
    }

    public function emptyLine(): string
    {
        return __('Nobody is written down as connected to :name yet.', ['name' => $this->contact->first_name ?? $this->contact->name]);
    }

    public function addRelationshipUrl(): string
    {
        return route('contacts.relationships.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    /**
     * How many of the people linked here are a name and nothing else.
     */
    public function namesWaiting(): int
    {
        return once(fn (): int => $this->relationships()
            ->filter(fn (Relationship $relationship): bool => $this->other($relationship)->is_partial)
            ->count());
    }

    public function namesWaitingLine(): string
    {
        if ($this->namesWaiting() === 0) {
            return '';
        }

        return trans_choice(
            '{1}One name is waiting for a record|:count names are waiting for a record',
            $this->namesWaiting(),
        );
    }

    public function namesWaitingUrl(): string
    {
        return route('contacts.index', [
            'vault' => $this->vault,
            'filter' => ContactGroupingEnum::NamesOnly->value,
        ]);
    }

    /** @return Collection<int, Relationship> */
    private function relationships(): Collection
    {
        return once(fn (): Collection => Relationship::query()
            ->forContact($this->contact)
            ->with(['relationshipType.relationshipCategory', 'contactA.avatar', 'contactB.avatar'])
            ->orderBy('id')
            ->get());
    }

    /**
     * The person at the far end of the link, which is whichever side the contact is not.
     */
    private function other(Relationship $relationship): Contact
    {
        return $relationship->contact_a_id === $this->contact->id
            ? $relationship->contactB
            : $relationship->contactA;
    }

    /**
     * How the link reads from this contact's side, which is the word the far side carries.
     *
     * The category alone cannot tell a father from a brother, so the row says which it is.
     *
     * @param  array<int, int>  $ages
     * @return array<string, mixed>
     */
    private function row(Relationship $relationship, array $ages): array
    {
        $person = $this->other($relationship);
        $type = $relationship->relationshipType;

        return [
            'name' => $person->name,
            'role' => $relationship->contact_a_id === $this->contact->id ? $type->role_b : $type->role_a,
            'url' => $person->is_partial
                ? null
                : route('contacts.show', ['vault' => $this->vault, 'contact' => $person]),
            'avatarUrl' => $person->avatar === null
                ? null
                : route('files.show', ['vault' => $this->vault, 'file' => $person->avatar]),
            'age' => $person->is_partial ? null : ($ages[$person->id] ?? null),
            'note' => $person->is_partial ? __('Name only, no profile yet') : null,
            'hasEnded' => $relationship->status === RelationshipStatusEnum::Ended,
            'whenLine' => $this->whenLine($relationship),
            'editUrl' => route('contacts.relationships.edit', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'relationship' => $relationship,
            ]),
            'destroyUrl' => route('contacts.relationships.destroy', [
                'vault' => $this->vault,
                'contact' => $this->contact,
                'relationship' => $relationship,
            ]),
        ];
    }

    /**
     * What the record holds about when the link ran, and never more than that.
     */
    private function whenLine(Relationship $relationship): ?string
    {
        $started = $relationship->started_on?->isoFormat('LL');
        $ended = $relationship->ended_on?->isoFormat('LL');

        if ($started !== null && $ended !== null) {
            return __(':start to :end', ['start' => $started, 'end' => $ended]);
        }

        if ($started !== null) {
            return __('Since :start', ['start' => $started]);
        }

        if ($ended !== null) {
            return __('Until :end', ['end' => $ended]);
        }

        return null;
    }
}
