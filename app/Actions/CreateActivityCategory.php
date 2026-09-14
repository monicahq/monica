<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;

/**
 * Add a group the account files its activity natures under, when the eight the product supplies do
 * not sort the vocabulary the way somebody thinks of it.
 */
class CreateActivityCategory
{
    private ActivityCategory $activityCategory;

    public function __construct(
        private readonly User $user,
        private string $name,
    ) {}

    public function execute(): ActivityCategory
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->create();
        $this->log();

        return $this->activityCategory;
    }

    private function create(): void
    {
        $positions = ActivityCategory::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->activityCategory = ActivityCategory::query()->create([
            'account_id' => $this->user->account_id,
            'key' => null,
            'name' => $this->name,
            'name_translation_key' => null,
            'is_system' => false,
            'is_active' => true,
            'position' => ($positions->max() ?? 0) + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityCategoryCreation,
            parameters: ['name' => $this->activityCategory->name],
            loggable: $this->activityCategory,
        )->onQueue('low');
    }
}
