<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Offer a life event category, and the natures under it, when a life event is recorded again, in the
 * place it always had.
 */
class ActivateLifeEventCategory
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventCategory $lifeEventCategory,
    ) {}

    public function execute(): LifeEventCategory
    {
        $this->validate();
        $this->activate();
        $this->log();

        return $this->lifeEventCategory;
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }
    }

    private function activate(): void
    {
        $this->lifeEventCategory->is_active = true;
        $this->lifeEventCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventCategoryActivated,
            parameters: ['name' => $this->lifeEventCategory->name],
            loggable: $this->lifeEventCategory,
        );
    }
}
