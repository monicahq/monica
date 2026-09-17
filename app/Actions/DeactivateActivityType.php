<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Retire a nature: it leaves the picker, and the activities already recorded with it keep their
 * nature, their icon and their custom fields. Nothing is deleted and the way back is the activation.
 */
class DeactivateActivityType
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityType $activityType,
    ) {}

    public function execute(): ActivityType
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->activityType;
    }

    private function validate(): void
    {
        if ($this->activityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity type is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->activityType->is_active = false;
        $this->activityType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityTypeDeactivated,
            parameters: ['name' => $this->activityType->name],
            loggable: $this->activityType,
        )->onQueue('low');
    }
}
