<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LifeEventCategoryEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Give a fresh account the groups it files its life event vocabulary under, so the first life event
 * somebody records has somewhere to be filed.
 *
 * The eight come in the order the product specification gives them, and Other is supplied last as
 * the place for whatever belongs nowhere else.
 */
class CreateDefaultLifeEventCategories
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<LifeEventCategory> */
    public function execute(): array
    {
        if ($this->user->account->lifeEventCategories()->exists()) {
            return [];
        }

        $categories = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $categories;
    }

    /** @return list<LifeEventCategory> */
    private function create(): array
    {
        $categories = [];
        $position = 1;

        foreach (LifeEventCategoryEnum::cases() as $case) {
            $categories[] = LifeEventCategory::query()->create([
                'account_id' => $this->user->account_id,
                'key' => $case->value,
                'name' => null,
                'name_translation_key' => $case->label(),
                'is_system' => true,
                'is_active' => true,
                'position' => $position,
            ]);

            $position++;
        }

        return $categories;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventCategoriesSeeded,
        );
    }
}
