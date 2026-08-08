<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Choose the vault a record lands in when nobody says which one.
 *
 * Exactly one vault per account is the default, so the one that held the title
 * loses it in the same breath. A deleted vault cannot take it: records would be
 * created in a compartment that is on its way out.
 */
class SetDefaultVault
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function execute(): Vault
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->vault;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->vault->trashed()) {
            throw new ModelNotFoundException('This vault is deleted.');
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            Vault::query()
                ->where('account_id', $this->user->account_id)
                ->whereKeyNot($this->vault->id)
                ->update(['is_default' => false]);

            $this->vault->is_default = true;
            $this->vault->save();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultDefaultSet,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
