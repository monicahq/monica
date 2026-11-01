<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotChangeASuppliedRelationshipCategory;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a display group the account added.
 *
 * A supplied category keeps the name it came with, because it is the one the product's own words
 * are written against and because renaming it would say something the key underneath does not.
 */
class UpdateRelationshipCategory
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipCategory $relationshipCategory,
        private string $name,
    ) {}

    public function execute(): RelationshipCategory
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->validate();
        $this->update();
        $this->log();

        return $this->relationshipCategory;
    }

    private function validate(): void
    {
        if ($this->relationshipCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship category is in another account.');
        }

        if ($this->relationshipCategory->is_system) {
            throw new CannotChangeASuppliedRelationshipCategory('This category came with the account.');
        }
    }

    private function update(): void
    {
        $this->relationshipCategory->name = $this->name;
        $this->relationshipCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCategoryUpdate,
            parameters: ['name' => $this->relationshipCategory->name],
            loggable: $this->relationshipCategory,
        );
    }
}
