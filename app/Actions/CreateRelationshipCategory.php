<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;

/**
 * Add a display group of the account's own, so a long list of relationship types can be read in the
 * order somebody actually thinks in.
 */
class CreateRelationshipCategory
{
    private RelationshipCategory $relationshipCategory;

    public function __construct(
        private readonly User $user,
        private string $name,
    ) {}

    public function execute(): RelationshipCategory
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->create();
        $this->log();

        return $this->relationshipCategory;
    }

    private function create(): void
    {
        $positions = RelationshipCategory::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->relationshipCategory = RelationshipCategory::query()->create([
            'account_id' => $this->user->account_id,
            'key' => null,
            'name' => $this->name,
            'name_translation_key' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => ($positions->max() ?? 0) + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCategoryCreation,
            parameters: ['name' => $this->relationshipCategory->name],
            loggable: $this->relationshipCategory,
        )->onQueue('low');
    }
}
