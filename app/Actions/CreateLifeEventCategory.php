<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;

/**
 * Add a group the account files its life event natures under, when the eight the product supplies do
 * not sort the vocabulary the way somebody thinks of it.
 */
class CreateLifeEventCategory
{
    private LifeEventCategory $lifeEventCategory;

    public function __construct(
        private readonly User $user,
        private string $name,
    ) {}

    public function execute(): LifeEventCategory
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->create();
        $this->log();

        return $this->lifeEventCategory;
    }

    private function create(): void
    {
        $positions = LifeEventCategory::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('position');

        $this->lifeEventCategory = LifeEventCategory::query()->create([
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
            action: UserActionEnum::LifeEventCategoryCreation,
            parameters: ['name' => $this->lifeEventCategory->name],
            loggable: $this->lifeEventCategory,
        )->onQueue('low');
    }
}
