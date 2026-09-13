<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a relationship category, and the types under it, for new relationships again.
 */
class ActivateRelationshipCategory
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipCategory $relationshipCategory,
    ) {}

    public function execute(): RelationshipCategory
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->relationshipCategory;
    }

    private function validate(): void
    {
        if ($this->relationshipCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship category is in another account.');
        }
    }

    private function activate(): void
    {
        $this->relationshipCategory->is_active = true;
        $this->relationshipCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCategoryActivated,
            parameters: ['name' => $this->relationshipCategory->name],
            loggable: $this->relationshipCategory,
        )->onQueue('low');
    }
}
