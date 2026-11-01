<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Remove a kind of link somebody invented and never used.
 *
 * A supplied type is never deleted, and neither is one that relationships are written with. Both
 * are retired instead, by no longer being offered, which leaves everything already written
 * readable.
 */
class DestroyRelationshipType
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly RelationshipType $relationshipType,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }

        $this->user->authorize('delete', $this->relationshipType);
    }

    private function destroy(): void
    {
        $this->name = $this->relationshipType->name;

        $this->relationshipType->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipTypeDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->relationshipType,
        );
    }
}
