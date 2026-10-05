<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Pennant\Feature;

/**
 * Turn a flag on for every account, those created afterwards included. The decision is stored as the
 * flag's answer for nobody in particular, which every flag reads for an account asked the first time,
 * and every answer already stored is overwritten so the latest switch wins.
 */
class ActivateFlagForEveryAccount
{
    public function __construct(
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
        Feature::for(null)->activate($this->flag);
        Feature::activateForEveryone($this->flag);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::FlagActivatedForEveryAccount,
            parameters: ['flag' => $this->flag],
        )->onQueue('low');
    }
}
