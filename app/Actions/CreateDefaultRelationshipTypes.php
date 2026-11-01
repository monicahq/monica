<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RelationshipCategoryEnum;
use App\Enums\RelationshipTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Give a fresh account the vocabulary it describes relationships with, so the first link somebody
 * writes down has words to be written in.
 *
 * The five categories come first and the ten types are filed under four of them. Other is supplied
 * empty, as the place for whatever belongs nowhere else.
 */
class CreateDefaultRelationshipTypes
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<RelationshipType> */
    public function execute(): array
    {
        if ($this->user->account->relationshipCategories()->exists()) {
            return [];
        }

        $types = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $types;
    }

    /** @return list<RelationshipType> */
    private function create(): array
    {
        $categories = $this->createCategories();
        $types = [];
        $positions = [];

        foreach (RelationshipTypeEnum::cases() as $case) {
            $key = $case->category()->value;
            $positions[$key] = ($positions[$key] ?? 0) + 1;

            $types[] = RelationshipType::query()->create([
                'account_id' => $this->user->account_id,
                'relationship_category_id' => $categories[$key]->id,
                'key' => $case->value,
                'name' => null,
                'name_translation_key' => $case->label(),
                'role_a' => null,
                'role_a_translation_key' => $case->roleA(),
                'role_b' => null,
                'role_b_translation_key' => $case->roleB(),
                'is_symmetric' => $case->isSymmetric(),
                'system_behavior' => $case->behavior(),
                'is_system' => true,
                'is_active' => true,
                'position' => $positions[$key],
            ]);
        }

        return $types;
    }

    /** @return array<string, RelationshipCategory> */
    private function createCategories(): array
    {
        $categories = [];
        $position = 1;

        foreach (RelationshipCategoryEnum::cases() as $case) {
            $categories[$case->value] = RelationshipCategory::query()->create([
                'account_id' => $this->user->account_id,
                'key' => $case->value,
                'name' => null,
                'name_translation_key' => $case->label(),
                'is_system' => true,
                'is_active' => true,
                'position' => $position,
            ]);

            $position++;
        }

        return $categories;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipTypesSeeded,
        );
    }
}
