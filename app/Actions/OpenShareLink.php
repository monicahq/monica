<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ShareLinkNeedsAPassword;
use App\Exceptions\ShareLinkPasswordIsWrong;
use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Open a private link, from the side of whoever received it.
 *
 * It is the only action with no user behind it, and that is the feature rather
 * than an omission: the recipient needs no account, is never asked to make one,
 * and never becomes a user of the account they are looking into. Nothing here
 * writes to what is shared, and nothing built on top of it ever may.
 *
 * An unknown, expired or revoked token all come back as the same not found, so
 * whoever is trying tokens learns nothing from the difference. A link asking
 * for a password and given none is not a failure but the normal first step, and
 * writes nothing down; a wrong password is written down before it is refused,
 * because a run of those is the only signal the owner ever gets that a link may
 * have leaked.
 *
 * What a successful open records is coarse on purpose. The visitor never agreed
 * to anything with us, so it is enough to answer "was this opened, roughly
 * when, roughly from where" and no more.
 */
class OpenShareLink
{
    private ShareLink $shareLink;

    public function __construct(
        private readonly string $token,
        private readonly ?string $password = null,
        private readonly ?string $ip = null,
        private readonly ?string $userAgent = null,
        private readonly ?string $country = null,
    ) {}

    public function execute(): ShareLink
    {
        $this->validate();
        $this->checkThePassword();
        $this->open();

        return $this->shareLink;
    }

    private function validate(): void
    {
        $shareLink = ShareLink::query()
            ->where('token', hash('sha256', $this->token))
            ->first();

        if ($shareLink === null || ! $shareLink->isUsable()) {
            throw new ModelNotFoundException('This link no longer works.');
        }

        $this->shareLink = $shareLink;
    }

    private function checkThePassword(): void
    {
        if (! $this->shareLink->isProtectedByAPassword()) {
            return;
        }

        if ($this->password === null) {
            throw new ShareLinkNeedsAPassword('This link asks for a password.');
        }

        if (Hash::check($this->password, (string) $this->shareLink->password_hash)) {
            return;
        }

        $this->writeTheVisit(failed: true);

        throw new ShareLinkPasswordIsWrong('This password does not open this link.');
    }

    private function open(): void
    {
        DB::transaction(function (): void {
            $this->writeTheVisit(failed: false);

            $this->shareLink->increment('view_count');
            $this->shareLink->last_viewed_at = now();
            $this->shareLink->save();
        });
    }

    /**
     * Write the opening down. The counters on the link are what survive the
     * pruning of these rows later, which is why both exist.
     */
    private function writeTheVisit(bool $failed): void
    {
        ShareLinkVisit::query()->create([
            'account_id' => $this->shareLink->account_id,
            'share_link_id' => $this->shareLink->id,
            'ip_address' => $this->ip,
            'user_agent' => $this->userAgent === null ? null : mb_substr($this->userAgent, 0, ShareLinkVisit::USER_AGENT_LENGTH),
            'country' => $this->country,
            'password_was_required' => $this->shareLink->isProtectedByAPassword(),
            'password_failed' => $failed,
            'viewed_at' => now(),
        ]);
    }
}
