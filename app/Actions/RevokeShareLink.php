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
 * Close a private link. It stops working at once, and it never works again.
 *
 * There is no undoing this and no reactivating: somebody revoking a link
 * believes it has gone somewhere it should not have, and a link that could come
 * back would make that belief wrong. What they get instead is a new link, with
 * a new token, which whoever held the old one does not have.
 *
 * The link itself stays, and so does every visit written against it. The point
 * of revoking something is often to find out what was read before it was
 * revoked, and a row deleted here would take that answer with it.
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
