<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RelationshipStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\RelationshipDatesAreOutOfOrder;
use App\Exceptions\RelationshipTypeIsNotAvailable;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Correct a link as seen from one of its two people: its nature, the role that person holds,
 * whether it is still going, when it ran, and, when the other person has no record of their own,
 * their name and whether they get one. Which two people it joins is not correctable.
 */
class UpdateRelationship
{
    use RefusesALoopInParentage;

    private Contact $other;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly Relationship $relationship,
        private readonly RelationshipType $relationshipType,
        private readonly RelationshipStatusEnum $status,
        private readonly ?CarbonImmutable $startedOn = null,
        private readonly ?CarbonImmutable $endedOn = null,
        private readonly bool $contactHoldsRoleA = true,
        private readonly ?string $otherFirstName = null,
        private readonly ?string $otherLastName = null,
        private readonly bool $createsRecord = false,
    ) {}

    public function execute(): Relationship
    {
        $this->validate();

        DB::transaction(function (): void {
            [$sideA, $sideB] = $this->orderTheSides();

            $this->refuseALoopInParentage(
                relationshipType: $this->relationshipType,
                sideA: $sideA,
                sideB: $sideB,
                accountId: $this->user->account_id,
                vaultId: $this->vault->id,
                ignoring: $this->relationship->id,
            );

            $this->relationship->update([
                'relationship_type_id' => $this->relationshipType->id,
                'contact_a_id' => $sideA->id,
                'contact_b_id' => $sideB->id,
                'status' => $this->status,
                'started_on' => $this->startedOn,
                'ended_on' => $this->status === RelationshipStatusEnum::Current ? null : $this->endedOn,
            ]);

            $this->giveTheOtherSideARecord();
            $this->renameTheOtherSide();
        });

        $this->relationship->refresh();

        $this->log();

        return $this->relationship;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->relationship->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship is in another account.');
        }

        if ($this->relationship->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This relationship is in another vault.');
        }

        if (! $this->relationship->involves($this->contact)) {
            throw new ModelNotFoundException('This contact is not on this relationship.');
        }

        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }

        if (! $this->relationshipType->is_active && $this->relationshipType->id !== $this->relationship->relationship_type_id) {
            throw new RelationshipTypeIsNotAvailable(__('That relationship type can no longer be chosen.'));
        }

        $this->other = $this->relationship->contact_a_id === $this->contact->id
            ? $this->relationship->contactB
            : $this->relationship->contactA;

        if (($this->otherFirstName !== null || $this->createsRecord) && ! $this->other->is_partial) {
            throw new ModelNotFoundException('This contact has a record of their own.');
        }

        if ($this->status === RelationshipStatusEnum::Current) {
            return;
        }

        if ($this->startedOn !== null && $this->endedOn !== null && $this->endedOn->lessThan($this->startedOn)) {
            throw new RelationshipDatesAreOutOfOrder(__('The end cannot fall before the start.'));
        }
    }

    /**
     * Which person carries which role once the nature is settled.
     *
     * A symmetric nature says the same word on both sides, so the pair is put in its one canonical
     * order rather than asked which way round it goes.
     *
     * @return array{0: Contact, 1: Contact}
     */
    private function orderTheSides(): array
    {
        if ($this->relationshipType->is_symmetric) {
            return $this->contact->id <= $this->other->id ? [$this->contact, $this->other] : [$this->other, $this->contact];
        }

        return $this->contactHoldsRoleA ? [$this->contact, $this->other] : [$this->other, $this->contact];
    }

    private function giveTheOtherSideARecord(): void
    {
        if (! $this->createsRecord) {
            return;
        }

        $this->other->is_partial = false;
        $this->other->save();
    }

    private function renameTheOtherSide(): void
    {
        if ($this->otherFirstName === null) {
            return;
        }

        if ($this->otherFirstName === $this->other->first_name && $this->otherLastName === $this->other->last_name) {
            return;
        }

        new UpdateContactName(
            user: $this->user,
            contact: $this->other,
            prefix: $this->other->prefix,
            firstName: $this->otherFirstName,
            middleName: $this->other->middle_name,
            lastName: $this->otherLastName,
            suffix: $this->other->suffix,
            maidenName: $this->other->maiden_name,
            nickname: $this->other->nickname,
            nameOrder: $this->other->name_order,
        )->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipUpdate,
            vault: $this->vault,
            parameters: [
                'name' => $this->relationship->contactA->name,
                'other' => $this->relationship->contactB->name,
            ],
            loggable: $this->relationship,
        );
    }
}
