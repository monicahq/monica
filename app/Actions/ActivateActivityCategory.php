<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer an activity category, and the natures under it, when an activity is recorded again, in the
 * place it always had.
 */
class ActivateActivityCategory
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityCategory $activityCategory,
    ) {}

    public function execute(): ActivityCategory
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->activityCategory;
    }

    private function validate(): void
    {
        if ($this->activityCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity category is in another account.');
        }
    }

    private function activate(): void
    {
        $this->activityCategory->is_active = true;
        $this->activityCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityCategoryActivated,
            parameters: ['name' => $this->activityCategory->name],
            loggable: $this->activityCategory,
        )->onQueue('low');
    }
}
