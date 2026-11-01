<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Exceptions\CannotDeleteARelationshipCategoryInUse;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Remove a display group the account added and then emptied.
 *
 * A category holding types is never removed with them inside: the types would lose the only place
 * they are listed. They are moved elsewhere first, which is a decision only the user can make.
 */
class DestroyRelationshipCategory
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly RelationshipCategory $relationshipCategory,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->relationshipCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship category is in another account.');
        }

        if ($this->relationshipCategory->is_system) {
            throw new CannotChangeASuppliedRelationshipCategory('This category came with the account and stays.');
        }

        $held = RelationshipType::query()
            ->where('relationship_category_id', $this->relationshipCategory->id)
            ->count();

        if ($held > 0) {
            throw new CannotDeleteARelationshipCategoryInUse("This category still holds {$held} types.");
        }
    }

    private function destroy(): void
    {
        $this->name = $this->relationshipCategory->name;

        $this->relationshipCategory->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCategoryDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->relationshipCategory,
        );
    }
}
