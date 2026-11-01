<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Retire a nature: it leaves the picker, and the life events already recorded with it keep their
 * nature, their icon and their custom fields. Nothing is deleted and the way back is the activation.
 */
class DeactivateLifeEventType
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventType $lifeEventType,
    ) {}

    public function execute(): LifeEventType
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->lifeEventType;
    }

    private function validate(): void
    {
        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->lifeEventType->is_active = false;
        $this->lifeEventType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypeDeactivated,
            parameters: ['name' => $this->lifeEventType->name],
            loggable: $this->lifeEventType,
        );
    }
}
