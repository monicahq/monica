<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Remove a group the account added and never filed anything under.
 *
 * Two categories stay. One the product supplied, because the wording it falls back to belongs to the
 * product. One that still holds natures, because removing it would take them with it. Retiring is
 * the answer in both cases, and it leaves everything already written reading as it does.
 */
class DestroyLifeEventCategory
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventCategory $lifeEventCategory,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }

        $this->user->authorize('delete', $this->lifeEventCategory);
    }

    private function destroy(): void
    {
        $this->lifeEventCategory->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventCategoryDeletion,
            parameters: ['name' => $this->lifeEventCategory->name],
            loggable: $this->lifeEventCategory,
        );
    }
}
