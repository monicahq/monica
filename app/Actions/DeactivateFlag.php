<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Pennant\Feature;

/**
 * Turn a flag off for one account. The capability it guards disappears for that person and what
 * they made with it stays, ready for the day the flag comes back on.
 */
class DeactivateFlag
{
    public function __construct(
        private readonly Account $account,
        private readonly string $flag,
        private readonly ?User $administrator = null,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->deactivate();
        $this->log();
    }

    private function validate(): void
    {
        if (! in_array($this->flag, Feature::defined(), true)) {
            throw new ModelNotFoundException("No flag is named {$this->flag}");
        }
    }

    private function deactivate(): void
    {
        $this->account->features()->deactivate($this->flag);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::FlagDeactivated,
            account: $this->account,
            parameters: ['flag' => $this->flag, 'name' => $this->account->user?->name],
        )->onQueue('low');
    }
}
