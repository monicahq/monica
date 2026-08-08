<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a list on the settings screen, so the ones somebody edits often are the
 * ones they reach first.
 *
 * The list is taken out and put back at the position asked for, and every list
 * is then numbered from one again. A position past the end puts it last.
 */
class UpdateValueListPosition
{
    public function __construct(
        private readonly User $user,
        private readonly ValueList $valueList,
        private readonly int $position,
    ) {}

    public function execute(): ValueList
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->valueList;
    }

    private function validate(): void
    {
        if ($this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }
    }

    private function update(): void
    {
        $lists = ValueList::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (ValueList $list): bool => $list->is($this->valueList))
            ->values();

        $lists->splice(max(0, $this->position - 1), 0, [$this->valueList]);

        DB::transaction(function () use ($lists): void {
            $lists->each(function (ValueList $list, int $index): void {
                $list->position = $index + 1;
                $list->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListPositionUpdate,
            parameters: ['name' => $this->valueList->name],
            loggable: $this->valueList,
        )->onQueue('low');
    }
}
