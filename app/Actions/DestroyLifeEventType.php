<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a life event type the product did not supply.
 *
 * The delete is soft, so the structure it owns is deliberately left where it is: a restored type has
 * to come back with the fields it asked for. Nothing here has to tidy the structure away, because
 * the only deletion that is final is the account, and the database cascades that.
 */
class DestroyLifeEventType
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly LifeEventType $lifeEventType,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }

        $this->user->authorize('delete', $this->lifeEventType);
    }

    private function destroy(): void
    {
        $this->name = $this->lifeEventType->name;

        $this->lifeEventType->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypeDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->lifeEventType,
        );
    }
}
