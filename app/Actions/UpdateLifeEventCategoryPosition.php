<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put a life event category somewhere else in the list, so the groups somebody reaches for sit at the
 * top of the settings, the pickers and the journal filters.
 *
 * Every category of the account is numbered from one again, the retired ones counted among them, so
 * a category switched back on finds the place it had. A position past the end lands last.
 */
class UpdateLifeEventCategoryPosition
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventCategory $lifeEventCategory,
        private readonly int $position,
    ) {}

    public function execute(): LifeEventCategory
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->lifeEventCategory;
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }
    }

    private function update(): void
    {
        $categories = LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (LifeEventCategory $category): bool => $category->is($this->lifeEventCategory))
            ->values();

        $categories->splice(max(0, $this->position - 1), 0, [$this->lifeEventCategory]);

        DB::transaction(function () use ($categories): void {
            $categories->each(function (LifeEventCategory $category, int $index): void {
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
            action: UserActionEnum::LifeEventCategoryPositionUpdate,
            parameters: ['name' => $this->lifeEventCategory->name],
            loggable: $this->lifeEventCategory,
        );
    }
}
