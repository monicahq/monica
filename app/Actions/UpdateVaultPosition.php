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
 * Move a vault in the list, so the one somebody lives in is the one they reach
 * first.
 *
 * The vault is taken out of the list and put back at the position asked for,
 * and every vault is then numbered from one again. A position past the end puts
 * it last, which is what dropping something at the bottom of a list means.
 */
class UpdateVaultPosition
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly int $position,
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
    }

    private function update(): void
    {
        $vaults = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (Vault $vault): bool => $vault->is($this->vault))
            ->values();

        $vaults->splice(max(0, $this->position - 1), 0, [$this->vault]);

        DB::transaction(function () use ($vaults): void {
            $vaults->each(function (Vault $vault, int $index): void {
                $vault->position = $index + 1;
                $vault->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            vault: $this->vault,
            user: $this->user,
            action: UserActionEnum::VaultPositionUpdate,
            parameters: ['name' => $this->vault->name],
            loggable: $this->vault,
        )->onQueue('low');
    }
}
