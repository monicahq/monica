<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotChangeARelationshipTypeDirection;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Helpers\RelationshipTypeNames;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change the words a kind of link reads with, or the category it is filed under.
 *
 * A supplied type may be reworded and moved like any other, and keeps its stable key and its
 * protected behaviour through it. What may not change is its direction, because the two people of
 * every relationship already written with it are recorded in that order, and turning it round would
 * rewrite what they say without anybody asking. A type somebody is already using is frozen the same
 * way, for the same reason.
 */
class UpdateRelationshipType
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipType $relationshipType,
        private readonly RelationshipCategory $relationshipCategory,
        private string $name,
        private string $roleA,
        private string $roleB,
        private readonly bool $isSymmetric,
        private readonly bool $isActive,
    ) {}

    public function execute(): RelationshipType
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->relationshipType;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->roleA = TextSanitizer::plainText($this->roleA);
        $this->roleB = $this->isSymmetric ? $this->roleA : TextSanitizer::plainText($this->roleB);
    }

    private function validate(): void
    {
        if ($this->relationshipType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship type is in another account.');
        }

        if ($this->relationshipCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship category is in another account.');
        }

        if ($this->isSymmetric !== $this->relationshipType->is_symmetric && $this->directionIsFrozen()) {
            throw new CannotChangeARelationshipTypeDirection('This type keeps the direction it was written with.');
        }

        if (RelationshipTypeNames::taken($this->user, $this->name, except: $this->relationshipType)) {
            throw new RelationshipTypeNameIsAlreadyUsed("The name {$this->name} is already used in this account.");
        }
    }

    private function directionIsFrozen(): bool
    {
        return $this->relationshipType->is_system || $this->relationshipType->relationshipCount() > 0;
    }

    private function update(): void
    {
        $this->relationshipType->relationship_category_id = $this->relationshipCategory->id;
        $this->relationshipType->name = $this->name;
        $this->relationshipType->name_translation_key = null;
        $this->relationshipType->role_a = $this->roleA;
        $this->relationshipType->role_a_translation_key = null;
        $this->relationshipType->role_b = $this->roleB;
        $this->relationshipType->role_b_translation_key = null;
        $this->relationshipType->is_symmetric = $this->isSymmetric;
        $this->relationshipType->is_active = $this->isActive;
        $this->relationshipType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipTypeUpdate,
            parameters: ['name' => $this->relationshipType->name],
            loggable: $this->relationshipType,
        )->onQueue('low');
    }
}
