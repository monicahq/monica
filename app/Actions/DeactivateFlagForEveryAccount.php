<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Pennant\Feature;

/**
 * Turn a flag off for every account, those created afterwards included. What anybody made with the
 * capability stays, ready for the day the flag comes back on.
 */
class DeactivateFlagForEveryAccount
{
    public function __construct(
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
        Feature::for(null)->deactivate($this->flag);
        Feature::deactivateForEveryone($this->flag);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::FlagDeactivatedForEveryAccount,
            parameters: ['flag' => $this->flag],
        )->onQueue('low');
    }
}
