<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put an activity category somewhere else in the list, so the groups somebody reaches for sit at the
 * top of the settings, the pickers and the journal filters.
 *
 * Every category of the account is numbered from one again, the retired ones counted among them, so
 * a category switched back on finds the place it had. A position past the end lands last.
 */
class UpdateActivityCategoryPosition
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityCategory $activityCategory,
        private readonly int $position,
    ) {}

    public function execute(): ActivityCategory
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->activityCategory;
    }

    private function validate(): void
    {
        if ($this->activityCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity category is in another account.');
        }
    }

    private function update(): void
    {
        $categories = ActivityCategory::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (ActivityCategory $category): bool => $category->is($this->activityCategory))
            ->values();

        $categories->splice(max(0, $this->position - 1), 0, [$this->activityCategory]);

        DB::transaction(function () use ($categories): void {
            $categories->each(function (ActivityCategory $category, int $index): void {
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
            action: UserActionEnum::ActivityCategoryPositionUpdate,
            parameters: ['name' => $this->activityCategory->name],
            loggable: $this->activityCategory,
        )->onQueue('low');
    }
}
