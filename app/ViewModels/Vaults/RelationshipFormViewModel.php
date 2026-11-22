<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;

class RelationshipFormViewModel
{
    public const int CANDIDATE_LIMIT = 25;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly string $query = '',
        private readonly string $preselectedTypeId = '',
        private readonly bool $opensSwapped = false,
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
        return __('Add a relationship');
    }

    public function description(): string
    {
        return __('Say how somebody is connected to :name.', ['name' => $this->contact->name]);
    }

    public function action(): string
    {
        return route('contacts.relationships.create', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function method(): string
    {
        return 'post';
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function peopleUrl(): string
    {
        return route('contacts.index', ['vault' => $this->vault]);
    }

    public function searchUrl(): string
    {
        return route('contacts.relationships.new', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function query(): string
    {
        return $this->query;
    }

    /** @return array<int, array{key: string|null, label: string, types: array<int, array{value: int, label: string, name: string, roleA: string, roleB: string, isSymmetric: bool}>}> */
    public function typeGroups(): array
    {
        return $this->choices()->groups();
    }

    public function hasTypes(): bool
    {
        return $this->choices()->any();
    }

    /** @return array<int, array{roleA: string, roleB: string, isSymmetric: bool}> */
    public function typeMap(): array
    {
        return $this->choices()->map();
    }

    private function choices(): RelationshipTypeChoices
    {
        return once(fn (): RelationshipTypeChoices => new RelationshipTypeChoices(accountId: $this->user->account_id));
    }

    public function noTypesLine(): string
    {
        return __('There is no relationship type to choose. Add one in your settings first.');
    }

    /** @return array<int, array{id: int, name: string, note: string, initials: string}> */
    public function candidates(): array
    {
        return Contact::query()
            ->where('vault_id', $this->vault->id)
            ->whereKeyNot($this->contact->id)
            ->matchingSearch($this->query, $this->user->locale)
            ->orderBy('sort_key')
            ->limit(self::CANDIDATE_LIMIT)
            ->get()
            ->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'name' => $contact->name,
                'note' => $this->candidateNote($contact),
                'initials' => $contact->initials,
            ])
            ->values()
            ->all();
    }

    public function noCandidatesLine(): string
    {
        return __('Nobody here matches “:query”. Choose “I know their name” to write them down instead.', [
            'query' => $this->query,
        ]);
    }

    /** @return array{typeId: string, mode: string, firstName: string, lastName: string, otherContactId: string, swapped: bool, createsRecord: bool} */
    public function values(): array
    {
        return [
            'typeId' => (string) old('relationship_type_id', $this->offeredTypeId()),
            'mode' => (string) old('mode', ''),
            'firstName' => (string) old('first_name', ''),
            'lastName' => (string) old('last_name', ''),
            'otherContactId' => (string) old('other_contact_id', ''),
            'swapped' => session()->hasOldInput() ? (bool) old('swapped') : $this->offeredSwap(),
            'createsRecord' => $this->ticked('creates_record'),
        ];
    }

    /**
     * Everything the form needs in one payload, so no translated string is written into a script.
     *
     * @return array{types: array<int, array{roleA: string, roleB: string, isSymmetric: bool}>, contactName: string, searchUrl: string, query: string, typeId: string, mode: string, firstName: string, lastName: string, otherContactId: string, otherContactName: string, swapped: bool, copy: array{nameless: string, sentence: string, inverse: string, ready: string, needOther: string}}
     */
    public function projection(): array
    {
        $values = $this->values();

        return [
            'types' => $this->typeMap(),
            'contactName' => $this->contact->name,
            'searchUrl' => $this->searchUrl(),
            'query' => $this->query,
            'typeId' => $values['typeId'],
            'mode' => $values['mode'],
            'firstName' => $values['firstName'],
            'lastName' => $values['lastName'],
            'otherContactId' => $values['otherContactId'],
            'otherContactName' => $this->otherContactName($values['otherContactId']),
            'swapped' => $values['swapped'],
            'copy' => [
                'nameless' => __('an unnamed :role'),
                'sentence' => __(':one is :two’s :role.'),
                'inverse' => __('On the other side: :sentence'),
                'ready' => __('Saved on both records at once.'),
                'needOther' => __('Say who the other side is.'),
            ],
        ];
    }

    /**
     * The type the form was opened with, when the account offers it. Anything else opens the form
     * with nothing chosen, since a stale or foreign link is no reason to refuse the screen.
     */
    private function offeredTypeId(): string
    {
        if (! array_key_exists((int) $this->preselectedTypeId, $this->typeMap())) {
            return '';
        }

        return $this->preselectedTypeId;
    }

    /**
     * The form opens with the roles swapped only when asked to and when the type it opens on has two
     * different roles, since a type whose roles are the same has nothing to swap.
     */
    private function offeredSwap(): bool
    {
        if (! $this->opensSwapped) {
            return false;
        }

        $typeId = $this->offeredTypeId();

        if ($typeId === '') {
            return false;
        }

        return ! $this->typeMap()[(int) $typeId]['isSymmetric'];
    }

    private function otherContactName(string $id): string
    {
        if ($id === '') {
            return '';
        }

        $contact = Contact::query()
            ->where('vault_id', $this->vault->id)
            ->find($id);

        return $contact instanceof Contact ? $contact->name : '';
    }

    private function candidateNote(Contact $contact): string
    {
        if ($contact->is_archived) {
            return __('Archived');
        }

        if ($contact->is_partial) {
            return __('Written down through a relationship, with no record of their own');
        }

        return __('Written down :when', ['when' => $contact->created_at->diffForHumans()]);
    }

    private function ticked(string $field): bool
    {
        if (! session()->hasOldInput()) {
            return false;
        }

        return session()->hasOldInput($field);
    }
}
