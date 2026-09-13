<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop offering a relationship category, and every type under it, when a new relationship is written. Everything already written keeps reading as it did.
 */
class DeactivateRelationshipCategory
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipCategory $relationshipCategory,
    ) {}

    public function execute(): RelationshipCategory
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->relationshipCategory;
    }

    private function validate(): void
    {
        if ($this->relationshipCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship category is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->relationshipCategory->is_active = false;
        $this->relationshipCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCategoryDeactivated,
            parameters: ['name' => $this->relationshipCategory->name],
            loggable: $this->relationshipCategory,
        )->onQueue('low');
    }
}
