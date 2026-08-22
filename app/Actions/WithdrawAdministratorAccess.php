<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Exceptions\InstanceMustKeepAnAdministrator;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Take the administrator capacity off somebody.
 *
 * Two rules hold this together, and both exist so an instance cannot be left
 * with nobody able to moderate. Nobody may do this to themselves, which is the
 * same rule as deactivation and for the same reason. And the last administrator
 * cannot be withdrawn at all.
 *
 * The count is taken under a lock, because the interesting failure is two
 * administrators withdrawing each other at the same moment. Both would read two
 * and both would pass, and the instance would end up with none.
 */
class WithdrawAdministratorAccess
{
    public function __construct(
        private readonly User $user,
        private readonly User $administrator,
    ) {}

    public function execute(): void
    {
        $this->validate();

        DB::transaction(function (): void {
            $this->guardTheLastOne();
            $this->withdraw();
        });

        $this->log();
    }

    private function validate(): void
    {
        if ($this->user->is($this->administrator)) {
            throw new AdministratorCannotLockThemselvesOut('An administrator cannot withdraw their own capacity. Another administrator has to do it.');
        }

        if (! $this->user->administrator()->exists()) {
            throw new ModelNotFoundException('That person does not administer this instance.');
        }
    }

    private function guardTheLastOne(): void
    {
        $held = Administrator::query()->lockForUpdate()->get();

        if ($held->count() <= 1) {
            throw new InstanceMustKeepAnAdministrator('An instance has to keep at least one administrator, so this one cannot be withdrawn.');
        }
    }

    private function withdraw(): void
    {
        Administrator::query()->where('user_id', $this->user->id)->delete();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::AdministratorAccessWithdrawn,
            account: $this->user->account,
            parameters: ['name' => $this->user->name],
            subject: $this->user,
        )->onQueue('low');
    }
}
