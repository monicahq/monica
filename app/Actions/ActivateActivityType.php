<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a nature again when an activity is recorded, in the place it always had.
 */
class ActivateActivityType
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityType $activityType,
    ) {}

    public function execute(): ActivityType
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->activityType;
    }

    private function validate(): void
    {
        if ($this->activityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity type is in another account.');
        }
    }

    private function activate(): void
    {
        $this->activityType->is_active = true;
        $this->activityType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityTypeActivated,
            parameters: ['name' => $this->activityType->name],
            loggable: $this->activityType,
        )->onQueue('low');
    }
}
