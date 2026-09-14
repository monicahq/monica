<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop offering an activity category, and every nature under it, when an activity is recorded.
 * Everything already written keeps its nature and reads exactly as it did.
 */
class DeactivateActivityCategory
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityCategory $activityCategory,
    ) {}

    public function execute(): ActivityCategory
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->activityCategory;
    }

    private function validate(): void
    {
        if ($this->activityCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity category is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->activityCategory->is_active = false;
        $this->activityCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityCategoryDeactivated,
            parameters: ['name' => $this->activityCategory->name],
            loggable: $this->activityCategory,
        )->onQueue('low');
    }
}
