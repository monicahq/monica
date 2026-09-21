<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a nature again when a life event is recorded, in the place it always had.
 */
class ActivateLifeEventType
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventType $lifeEventType,
    ) {}

    public function execute(): LifeEventType
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->lifeEventType;
    }

    private function validate(): void
    {
        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }
    }

    private function activate(): void
    {
        $this->lifeEventType->is_active = true;
        $this->lifeEventType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypeActivated,
            parameters: ['name' => $this->lifeEventType->name],
            loggable: $this->lifeEventType,
        )->onQueue('low');
    }
}
