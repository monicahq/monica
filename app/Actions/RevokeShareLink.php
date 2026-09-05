<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ShareLinkIsRevoked;
use App\Jobs\LogUserAction;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Close a private link.
 */
class RevokeShareLink
{
    public function __construct(
        private readonly User $user,
        private readonly ShareLink $shareLink,
    ) {}

    public function execute(): ShareLink
    {
        $this->validate();
        $this->revoke();
        $this->log();

        return $this->shareLink;
    }

    private function validate(): void
    {
        if ($this->shareLink->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This link is in another account.');
        }

        if ($this->shareLink->revoked_at !== null) {
            throw new ShareLinkIsRevoked('This link was already revoked.');
        }
    }

    private function revoke(): void
    {
        $this->shareLink->revoked_at = now();
        $this->shareLink->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ShareLinkRevocation,
            vault: $this->shareLink->vault,
            parameters: ['label' => $this->shareLink->label],
            loggable: $this->shareLink,
        )->onQueue('low');
    }
}
