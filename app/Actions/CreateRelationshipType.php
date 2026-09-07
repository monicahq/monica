<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Helpers\RelationshipTypeNames;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Add a kind of link the product did not supply, such as Godparent / Godchild.
 *
 * A type somebody invents is theirs entirely: it carries no stable key and no protected behaviour,
 * whatever it is called and wherever it is filed.
 */
class CreateRelationshipType
{
    private RelationshipType $relationshipType;

    public function __construct(
        private readonly User $user,
        private readonly RelationshipCategory $relationshipCategory,
        private string $name,
        private string $roleA,
        private string $roleB,
        private readonly bool $isSymmetric = true,
        private readonly bool $isActive = true,
    ) {}

    public function execute(): RelationshipType
    {
        $this->sanitize();
        $this->validate();
        $this->create();
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
        if ($this->relationshipCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship category is in another account.');
        }

        if (RelationshipTypeNames::taken($this->user, $this->name)) {
            throw new RelationshipTypeNameIsAlreadyUsed("The name {$this->name} is already used in this account.");
        }
    }

    private function create(): void
    {
        $positions = RelationshipType::query()
            ->where('account_id', $this->user->account_id)
            ->where('relationship_category_id', $this->relationshipCategory->id)
            ->pluck('position');

        $this->relationshipType = RelationshipType::query()->create([
            'account_id' => $this->user->account_id,
            'relationship_category_id' => $this->relationshipCategory->id,
            'key' => null,
            'name' => $this->name,
            'name_translation_key' => null,
            'role_a' => $this->roleA,
            'role_a_translation_key' => null,
            'role_b' => $this->roleB,
            'role_b_translation_key' => null,
            'is_symmetric' => $this->isSymmetric,
            'system_behavior' => null,
            'is_system' => false,
            'is_active' => $this->isActive,
            'position' => ($positions->max() ?? 0) + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipTypeCreation,
            parameters: ['name' => $this->relationshipType->name],
            loggable: $this->relationshipType,
        )->onQueue('low');
    }
}
