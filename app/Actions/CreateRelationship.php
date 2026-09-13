<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RelationshipBehaviorEnum;
use App\Enums\RelationshipStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotLinkSomebodyToThemselves;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Exceptions\RelationshipWouldCrossVaults;
use App\Exceptions\RelationshipWouldLoopParentage;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Record that two people are connected.
 *
 * One row holds both sides, so nothing is ever mirrored, and the other side is a real contact
 * whether it was already written down or is written down here.
 */
class CreateRelationship
{
    private Relationship $relationship;

    private Contact $other;

    private Contact $sideA;

    private Contact $sideB;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly RelationshipType $relationshipType,
        private readonly ?Contact $otherContact = null,
        private readonly ?string $firstName = null,
        private readonly ?string $lastName = null,
        private readonly bool $createsRecord = false,
        private readonly bool $swapped = false,
    ) {}

    public function execute(): Relationship
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->settleTheOtherSide();
            $this->orderTheSides();
            $this->refuseALoopInParentage();
            $this->create();
        });

        $this->log();

        return $this->relationship;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->contact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if ($this->contact->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This contact is in another vault.');
        }

        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }

        if (! $this->relationshipType->is_active) {
            throw new RelationshipTypeIsNotAvailable(__('That relationship type can no longer be chosen.'));
        }

        if ($this->otherContact === null) {
            return;
        }

        if ($this->otherContact->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This contact is in another account.');
        }

        if ($this->otherContact->vault_id !== $this->vault->id) {
            throw new RelationshipWouldCrossVaults(__('Both people have to be in the same vault.'));
        }

        if ($this->otherContact->id === $this->contact->id) {
            throw new CannotLinkSomebodyToThemselves(__('Somebody cannot be in a relationship with themselves.'));
        }
    }

    private function settleTheOtherSide(): void
    {
        if ($this->otherContact instanceof Contact) {
            $this->other = $this->otherContact;

            return;
        }

        $this->other = new CreateContact(
            user: $this->user,
            vault: $this->vault,
            firstName: $this->firstName,
            lastName: $this->lastName,
            isPartial: ! $this->createsRecord,
        )->execute();
    }

    private function orderTheSides(): void
    {
        if ($this->relationshipType->is_symmetric) {
            $lower = min($this->contact->id, $this->other->id);

            $this->sideA = $lower === $this->contact->id ? $this->contact : $this->other;
            $this->sideB = $lower === $this->contact->id ? $this->other : $this->contact;

            return;
        }

        $this->sideA = $this->swapped ? $this->other : $this->contact;
        $this->sideB = $this->swapped ? $this->contact : $this->other;
    }

    private function refuseALoopInParentage(): void
    {
        if ($this->relationshipType->system_behavior !== RelationshipBehaviorEnum::GenealogyParentage) {
            return;
        }

        $parentageTypes = $this->parentageTypeIds();
        $seen = [$this->sideA->id];
        $generation = [$this->sideA->id];

        while ($generation !== []) {
            $parents = Relationship::query()
                ->where('vault_id', $this->vault->id)
                ->whereIn('contact_b_id', $generation)
                ->whereIn('relationship_type_id', $parentageTypes)
                ->pluck('contact_a_id')
                ->all();

            if (in_array($this->sideB->id, $parents, true)) {
                throw new RelationshipWouldLoopParentage(
                    __('That would make somebody their own ancestor.')
                );
            }

            $generation = array_values(array_diff(array_unique($parents), $seen));
            $seen = array_merge($seen, $generation);
        }
    }

    /** @return array<int, int> */
    private function parentageTypeIds(): array
    {
        return RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->where('system_behavior', RelationshipBehaviorEnum::GenealogyParentage)
            ->pluck('id')
            ->all();
    }

    private function create(): void
    {
        $this->relationship = Relationship::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'relationship_type_id' => $this->relationshipType->id,
            'contact_a_id' => $this->sideA->id,
            'contact_b_id' => $this->sideB->id,
            'status' => RelationshipStatusEnum::Current,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCreation,
            vault: $this->vault,
            parameters: [
                'name' => $this->sideA->name,
                'role' => $this->relationshipType->role_a,
                'other' => $this->sideB->name,
            ],
            loggable: $this->relationship,
        )->onQueue('low');
    }
}
