<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\RelationshipStatusEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;

class RelationshipEditViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly Relationship $relationship,
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
        return __('Edit this relationship');
    }

    public function description(): string
    {
        return __('Between :one and :two.', [
            'one' => $this->relationship->contactA->name,
            'two' => $this->relationship->contactB->name,
        ]);
    }

    public function action(): string
    {
        return route('contacts.relationships.update', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'relationship' => $this->relationship,
        ]);
    }

    public function method(): string
    {
        return 'put';
    }

    public function backUrl(): string
    {
        return route('contacts.show', [
            'vault' => $this->vault,
            'contact' => $this->contact,
            'page' => ContactViewModel::PAGE_RELATIONSHIPS,
        ]);
    }

    /** @return array<int, array{key: string|null, label: string, types: array<int, array{value: int, label: string, name: string, roleA: string, roleB: string, isSymmetric: bool}>}> */
    public function typeGroups(): array
    {
        return $this->choices()->groups();
    }

    /** @return array<int, array{roleA: string, roleB: string, isSymmetric: bool}> */
    public function typeMap(): array
    {
        return $this->choices()->map();
    }

    /**
     * Which way round the link reads, named after the two people rather than after A and B.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function directions(): array
    {
        $type = $this->relationship->relationshipType;

        if ($type->is_symmetric) {
            return [];
        }

        return [
            ['value' => '0', 'label' => __(':name is the :role.', [
                'name' => $this->relationship->contactA->name,
                'role' => $type->role_a,
            ])],
            ['value' => '1', 'label' => __(':name is the :role.', [
                'name' => $this->relationship->contactB->name,
                'role' => $type->role_a,
            ])],
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    public function statuses(): array
    {
        return [
            ['value' => RelationshipStatusEnum::Current->value, 'label' => __('Still going')],
            ['value' => RelationshipStatusEnum::Ended->value, 'label' => __('Over')],
        ];
    }

    public function isSymmetric(): bool
    {
        return $this->relationship->relationshipType->is_symmetric;
    }

    /** @return array{typeId: string, status: string, startedOn: string, endedOn: string, swapped: string} */
    public function values(): array
    {
        return [
            'typeId' => (string) old('relationship_type_id', (string) $this->relationship->relationship_type_id),
            'status' => (string) old('status', $this->relationship->status->value),
            'startedOn' => (string) old('started_on', $this->relationship->started_on?->format('Y-m-d') ?? ''),
            'endedOn' => (string) old('ended_on', $this->relationship->ended_on?->format('Y-m-d') ?? ''),
            'swapped' => (string) old('swapped', '0'),
        ];
    }

    /**
     * The nature the link carries stays on the list even when the account has withdrawn it, so
     * saving without changing it never fails.
     */
    private function choices(): RelationshipTypeChoices
    {
        return once(fn (): RelationshipTypeChoices => new RelationshipTypeChoices(
            accountId: $this->user->account_id,
            keeping: $this->relationship->relationshipType,
        ));
    }
}
