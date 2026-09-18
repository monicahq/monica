<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Support\Facades\DB;

/**
 * Give a fresh account the value lists it starts with, so the first field somebody builds has
 * something to offer.
 */
class CreateDefaultValueLists
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<ValueList> */
    public function execute(): array
    {
        if ($this->user->account->valueLists()->exists()) {
            return [];
        }

        $lists = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $lists;
    }

    /** @return list<ValueList> */
    private function create(): array
    {
        $lists = [];
        $position = 1;

        foreach (ValueListEnum::cases() as $case) {
            $list = ValueList::query()->create([
                'account_id' => $this->user->account_id,
                'key' => $case->value,
                'name' => null,
                'name_translation_key' => $case->label(),
                'description' => $case->description(),
                'is_system' => true,
                'allows_user_values' => true,
                'position' => $position,
            ]);

            $this->createItems($list, $case);

            $lists[] = $list;
            $position++;
        }

        return $lists;
    }

    private function createItems(ValueList $list, ValueListEnum $case): void
    {
        $position = 1;
        $rows = [];

        foreach ($case->items() as $item) {
            $rows[] = [
                'account_id' => $this->user->account_id,
                'key' => (string) $item->value,
                'label' => null,
                'label_translation_key' => $item->label(),
                'position' => $position,
                'is_system' => true,
                'is_active' => true,
                'usage_count' => 0,
            ];

            $position++;
        }

        $list->items()->createMany($rows);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListsSeeded,
        )->onQueue('low');
    }
}
