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
 * Give a fresh account the value lists it starts with, so the first field
 * somebody builds has something to offer.
 *
 * Nothing here is protected. Every list and every item is ordinary data from
 * the moment it is written: it can be renamed, reordered, added to, disabled
 * or thrown away. What the `key` buys is the ability to find a list again, not
 * a promise that it still looks the way it was seeded.
 *
 * The names are not translated here. They are stored as translation keys and
 * read in the language of whoever is looking, which is what keeps a list
 * seeded in English readable to somebody who later switches to French.
 *
 * One entry goes in the log for the whole seeding rather than one per list,
 * since this happens once and nobody asked for it line by line.
 */
class CreateDefaultValueLists
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return list<ValueList>
     */
    public function execute(): array
    {
        $lists = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $lists;
    }

    /**
     * @return list<ValueList>
     */
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
