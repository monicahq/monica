<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Stop offering a life event category, and every nature under it, when a life event is recorded.
 * Everything already written keeps its nature and reads exactly as it did.
 */
class DeactivateLifeEventCategory
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventCategory $lifeEventCategory,
    ) {}

    public function execute(): LifeEventCategory
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->lifeEventCategory;
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }
    }

    private function deactivate(): void
    {
        $this->lifeEventCategory->is_active = false;
        $this->lifeEventCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventCategoryDeactivated,
            parameters: ['name' => $this->lifeEventCategory->name],
            loggable: $this->lifeEventCategory,
        )->onQueue('low');
    }
}
