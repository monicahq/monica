<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\ShareLinkIsRevoked;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ShareLink;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

/**
 * Change what an existing link shows, and to whom.
 */
class UpdateShareLink
{
    /**
     * @param  list<int>  $excludedSectionIds
     * @param  list<int>  $excludedLifeEventIds
     */
    public function __construct(
        private readonly User $user,
        private readonly ShareLink $shareLink,
        private string $label,
        private ?string $recipientHint = null,
        private readonly ?CarbonImmutable $expiresAt = null,
        private readonly ?string $password = null,
        private readonly bool $removesPassword = false,
        private readonly bool $includesFiles = false,
        private readonly bool $includesLifeEvents = false,
        private readonly bool $includesLifeEventNarrative = false,
        private readonly bool $includesDayNarrative = false,
        private readonly bool $includesRelationships = false,
        private readonly bool $includesGroups = false,
        private readonly bool $includesSensitiveFields = false,
        private readonly array $excludedSectionIds = [],
        private readonly array $excludedLifeEventIds = [],
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
            throw new ShareLinkIsRevoked('A revoked link never works again. Make a new one instead.');
        }
    }

    private function update(): void
    {
        $this->shareLink->label = $this->label;
        $this->shareLink->recipient_hint = $this->recipientHint;
        $this->shareLink->expires_at = $this->expiresAt;
        $this->shareLink->includes_files = $this->includesFiles;
        $this->shareLink->includes_life_events = $this->includesLifeEvents;
        $this->shareLink->includes_life_event_narrative = $this->includesLifeEventNarrative;
        $this->shareLink->includes_day_narrative = $this->includesDayNarrative;
        $this->shareLink->includes_relationships = $this->includesRelationships;
        $this->shareLink->includes_groups = $this->includesGroups;
        $this->shareLink->includes_sensitive_fields = $this->includesSensitiveFields;
        $this->shareLink->excluded_section_ids = $this->excludedSectionIds;
        $this->shareLink->excluded_life_event_ids = $this->excludedLifeEventIds;
        $this->shareLink->password_hash = $this->passwordHash();
        $this->shareLink->save();
    }

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
        );
    }
}
