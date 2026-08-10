<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteASuppliedList;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\ValueList;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a value list, and the choices it held with it.
 *
 * The deletion is soft, and it takes the items down with it so a list cannot
 * come back half empty.
 *
 * Only a list somebody made can go. A supplied one is what the product itself
 * reads from, and an account that does not track genders empties that list
 * rather than deleting it: an empty list offers nothing, which is the same
 * outcome without leaving the product pointing at nothing.
 *
 * Once fields exist, a list that one of them points at is disabled rather than
 * deleted, and the field says why. There is no field yet to ask.
 */
class DestroyValueList
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly ValueList $valueList,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->valueList->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This value list is in another account.');
        }

        if ($this->valueList->is_system) {
            throw new CannotDeleteASuppliedList('This value list was supplied by the product.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->valueList->name;

        DB::transaction(function (): void {
            $this->valueList->items()->delete();
            $this->valueList->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ValueListDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->valueList,
        )->onQueue('low');
    }
}
