<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ShareLinkIsRevoked;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ShareLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * Change what an existing link shows, and to whom.
 *
 * Everything the owner decided when they made it can be decided again: the
 * label, who it was meant for, when it stops working, the password, and every
 * inclusion. What cannot change is what the link points at, since a link
 * quietly repointed at somebody else is the accident this product cannot
 * afford, and the token, which stays what was sent out.
 *
 * The inclusions are written as a whole rather than merged, so the screen sends
 * the state of every box and a box left unticked closes what it names. Anything
 * still shown through the link stops being shown the moment this returns.
 *
 * A revoked link is not edited. Revocation is permanent, and the answer to
 * wanting a revoked link back is a new link.
 */
class UpdateShareLink
{
    /**
     * @param  list<int>  $excludedSectionIds
     * @param  list<int>  $excludedActivityIds
     */
    public function __construct(
        private readonly User $user,
        private readonly ShareLink $shareLink,
        private string $label,
        private ?string $recipientHint = null,
        private readonly ?Carbon $expiresAt = null,
        private readonly ?string $password = null,
        private readonly bool $removesPassword = false,
        private readonly bool $includesFiles = false,
        private readonly bool $includesActivities = false,
        private readonly bool $includesActivityNarrative = false,
        private readonly bool $includesDayNarrative = false,
        private readonly bool $includesRelationships = false,
        private readonly bool $includesGroups = false,
        private readonly bool $includesSensitiveFields = false,
        private readonly array $excludedSectionIds = [],
        private readonly array $excludedActivityIds = [],
    ) {}

    public function execute(): ShareLink
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->shareLink;
    }

    private function sanitize(): void
    {
        $this->label = TextSanitizer::plainText($this->label);
        $this->recipientHint = TextSanitizer::nullablePlainText($this->recipientHint);
    }

    private function validate(): void
    {
        if ($this->shareLink->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This link is in another account.');
        }

        if ($this->shareLink->revoked_at !== null) {
            throw new ShareLinkIsRevoked('A revoked link is never brought back. Make a new one instead.');
        }
    }

    private function update(): void
    {
        $this->shareLink->label = $this->label;
        $this->shareLink->recipient_hint = $this->recipientHint;
        $this->shareLink->expires_at = $this->expiresAt;
        $this->shareLink->includes_files = $this->includesFiles;
        $this->shareLink->includes_activities = $this->includesActivities;
        $this->shareLink->includes_activity_narrative = $this->includesActivityNarrative;
        $this->shareLink->includes_day_narrative = $this->includesDayNarrative;
        $this->shareLink->includes_relationships = $this->includesRelationships;
        $this->shareLink->includes_groups = $this->includesGroups;
        $this->shareLink->includes_sensitive_fields = $this->includesSensitiveFields;
        $this->shareLink->excluded_section_ids = $this->excludedSectionIds;
        $this->shareLink->excluded_activity_ids = $this->excludedActivityIds;
        $this->shareLink->password_hash = $this->passwordHash();
        $this->shareLink->save();
    }

    /**
     * Settle the password. Sending a new one replaces whatever was there,
     * asking for it to be removed takes it away, and saying nothing about it
     * leaves it alone, so editing the label of a protected link does not
     * quietly unprotect it.
     */
    private function passwordHash(): ?string
    {
        if ($this->removesPassword) {
            return null;
        }

        if ($this->password === null) {
            return $this->shareLink->password_hash;
        }

        return Hash::make($this->password);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ShareLinkUpdate,
            vault: $this->shareLink->vault,
            parameters: ['label' => $this->label],
            loggable: $this->shareLink,
        )->onQueue('low');
    }
}
