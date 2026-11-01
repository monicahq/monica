<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a relationship type for new relationships again, in the place it always had.
 */
class ActivateRelationshipType
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipType $relationshipType,
    ) {}

    public function execute(): RelationshipType
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->relationshipType;
    }

    private function validate(): void
    {
        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }
    }

    private function activate(): void
    {
        $this->relationshipType->is_active = true;
        $this->relationshipType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipTypeActivated,
            parameters: ['name' => $this->relationshipType->name],
            loggable: $this->relationshipType,
        );
    }
}
