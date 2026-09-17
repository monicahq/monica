<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete an activity type the product did not supply.
 *
 * The delete is soft, so the structure it owns is deliberately left where it is: a restored type has
 * to come back with the fields it asked for. Nothing here has to tidy the structure away, because
 * the only deletion that is final is the account, and the database cascades that.
 */
class DestroyActivityType
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly ActivityType $activityType,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->activityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity type is in another account.');
        }

        $this->user->authorize('delete', $this->activityType);
    }

    private function destroy(): void
    {
        $this->name = $this->activityType->name;

        $this->activityType->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityTypeDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->activityType,
        )->onQueue('low');
    }
}
