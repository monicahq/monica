<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Jobs\LogAdministratorAction;
use App\Models\User;

/**
 * Stop somebody signing in, without deleting anything they own.
 *
 * It takes effect on a session they already have open, which the `active`
 * middleware is what makes true: signing in is not the only moment the flag is
 * read, every request is.
 *
 * An administrator may not do this to themselves. On an instance with one
 * administrator it would lock the admin space and leave the only way back a
 * shell on the server, and somebody clicking the wrong row on their own account
 * should not be able to do that to themselves.
 */
class DeactivateUser
{
    public function __construct(
        private readonly User $user,
        private readonly User $administrator,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->deactivate();
        $this->log();

        return $this->user;
    }

    private function validate(): void
    {
        if ($this->user->is($this->administrator)) {
            throw new AdministratorCannotLockThemselvesOut('An administrator cannot deactivate themselves. Another administrator has to do it.');
        }
    }

    private function deactivate(): void
    {
        $this->user->is_active = false;
        $this->user->save();
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::UserDeactivated,
            account: $this->user->account,
            parameters: ['name' => $this->user->name],
            subject: $this->user,
        )->onQueue('low');
    }
}
