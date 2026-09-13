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
 * Correct a link somebody wrote down: what it is, which way round it reads, whether it is still
 * going, and when it ran.
 *
 * Which two people it joins is not correctable. Pointing a link at somebody else is a different
 * link, and it is written by removing this one and recording that one.
 *
 * A nature the account has since withdrawn stays on the links already carrying it, so saving a
 * link without changing its nature never fails. Choosing a withdrawn nature is refused.
 */
class UpdateRelationship
{
    use RefusesALoopInParentage;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Relationship $relationship,
        private readonly RelationshipType $relationshipType,
        private readonly RelationshipStatusEnum $status,
        private readonly ?CarbonImmutable $startedOn = null,
        private readonly ?CarbonImmutable $endedOn = null,
        private readonly bool $swapped = false,
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
                'ended_on' => $this->endedOn,
            ]);
        });

        $this->log();

        return $this->relationship->refresh();
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

        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }

        if (! $this->relationshipType->is_active && $this->relationshipType->id !== $this->relationship->relationship_type_id) {
            throw new RelationshipTypeIsNotAvailable(__('That relationship type can no longer be chosen.'));
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
        $sideA = $this->relationship->contactA;
        $sideB = $this->relationship->contactB;

        if ($this->relationshipType->is_symmetric) {
            return $sideA->id <= $sideB->id ? [$sideA, $sideB] : [$sideB, $sideA];
        }

        return $this->swapped ? [$sideB, $sideA] : [$sideA, $sideB];
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
        )->onQueue('low');
    }
}
