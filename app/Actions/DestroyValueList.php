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
