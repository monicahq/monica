<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a group the account files its activity natures under.
 *
 * A category the product supplied is renamed like any other. Being supplied governs deletion and
 * nothing else, which is what section 9.3 of the product specification says. The supplied wording is
 * dropped the moment somebody writes their own, so their words stand in every language from then on.
 */
class UpdateActivityCategory
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityCategory $activityCategory,
        private string $name,
    ) {}

    public function execute(): ActivityCategory
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->validate();
        $this->update();
        $this->log();

        return $this->activityCategory;
    }

    private function validate(): void
    {
        if ($this->activityCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity category is in another account.');
        }
    }

    private function update(): void
    {
        $this->activityCategory->name = $this->name;
        $this->activityCategory->name_translation_key = null;
        $this->activityCategory->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityCategoryUpdate,
            parameters: ['name' => $this->activityCategory->name],
            loggable: $this->activityCategory,
        )->onQueue('low');
    }
}
