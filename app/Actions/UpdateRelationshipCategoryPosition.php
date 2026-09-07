<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a display group up or down the list, so the groups somebody reads first are the ones they
 * reach first.
 */
class UpdateRelationshipCategoryPosition
{
    public function __construct(
        private readonly User $user,
        private readonly RelationshipCategory $relationshipCategory,
        private readonly int $position,
    ) {}

    public function execute(): RelationshipCategory
    {
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
    }

    private function update(): void
    {
        $categories = RelationshipCategory::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (RelationshipCategory $category): bool => $category->is($this->relationshipCategory))
            ->values();

        $categories->splice(max(0, $this->position - 1), 0, [$this->relationshipCategory]);

        DB::transaction(function () use ($categories): void {
            $categories->each(function (RelationshipCategory $category, int $index): void {
                $category->position = $index + 1;
                $category->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipCategoryPositionUpdate,
            parameters: ['name' => $this->relationshipCategory->name],
            loggable: $this->relationshipCategory,
        )->onQueue('low');
    }
}
