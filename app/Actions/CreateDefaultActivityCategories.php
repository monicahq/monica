<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ActivityCategoryEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Give a fresh account the groups it files its activity vocabulary under, so the first activity
 * somebody records has somewhere to be filed.
 *
 * The eight come in the order the product specification gives them, and Other is supplied last as
 * the place for whatever belongs nowhere else.
 */
class CreateDefaultActivityCategories
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<ActivityCategory> */
    public function execute(): array
    {
        $categories = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $categories;
    }

    /** @return list<ActivityCategory> */
    private function create(): array
    {
        $categories = [];
        $position = 1;

        foreach (ActivityCategoryEnum::cases() as $case) {
            $categories[] = ActivityCategory::query()->create([
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
            action: UserActionEnum::ActivityCategoriesSeeded,
        )->onQueue('low');
    }
}
