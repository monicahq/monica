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
