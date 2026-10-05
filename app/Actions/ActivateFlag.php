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
 * Turn a flag on for one account, so the capability it guards appears for that person. With no
 * administrator, the switch was made from the command line and is recorded as the console's.
 */
class ActivateFlag
{
    public function __construct(
        private readonly Account $account,
        private readonly string $flag,
        private readonly ?User $administrator = null,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->activate();
        $this->log();
    }

    private function validate(): void
    {
        if (! in_array($this->flag, Feature::defined(), true)) {
            throw new ModelNotFoundException("No flag is named {$this->flag}");
        }
    }

    private function activate(): void
    {
        $this->account->features()->activate($this->flag);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::FlagActivated,
            account: $this->account,
            parameters: ['flag' => $this->flag, 'name' => $this->account->user?->name],
        )->onQueue('low');
    }
}
