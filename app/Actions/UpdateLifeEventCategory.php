<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a group the account files its life event natures under.
 *
 * A category the product supplied is renamed like any other. Being supplied governs deletion and
 * nothing else, which is what section 9.3 of the product specification says. The supplied wording is
 * dropped the moment somebody writes their own, so their words stand in every language from then on.
 */
class UpdateLifeEventCategory
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventCategory $lifeEventCategory,
        private string $name,
    ) {}

    public function execute(): LifeEventCategory
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->validate();
        $this->update();
        $this->log();

        return $this->lifeEventCategory;
    }

    private function validate(): void
    {
        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }
    }

    private function update(): void
    {
        $this->lifeEventCategory->name = $this->name;
        $this->lifeEventCategory->name_translation_key = null;
        $this->lifeEventCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventCategoryUpdate,
            parameters: ['name' => $this->lifeEventCategory->name],
            loggable: $this->lifeEventCategory,
        )->onQueue('low');
    }
}
