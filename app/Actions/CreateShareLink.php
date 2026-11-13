<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Open a private link onto one contact page, the way a document is shared.
 */
class CreateShareLink
{
    private ShareLink $shareLink;

    private string $token;

    /**
     * @param  list<int>  $includedSectionIds
     * @param  list<int>  $excludedLifeEventIds
     */
    public function __construct(
        private readonly User $user,
        private readonly Contact|Vault $shareable,
        private string $label,
        private ?string $recipientHint = null,
        private readonly ?string $password = null,
        private readonly ?CarbonImmutable $expiresAt = null,
        private readonly bool $includesFiles = false,
        private readonly bool $includesLifeEvents = false,
        private readonly bool $includesLifeEventNarrative = false,
        private readonly bool $includesDayNarrative = false,
        private readonly bool $includesRelationships = false,
        private readonly bool $includesGroups = false,
        private readonly bool $includesSensitiveFields = false,
        private readonly array $includedSectionIds = [],
        private readonly array $excludedLifeEventIds = [],
    ) {}

    public function execute(): ShareLink
    {
        $this->sanitize();
        $this->validate();
        $this->create();
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
        if ($this->shareable->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This record is in another account.');
        }
    }

    private function create(): void
    {
        $this->token = Str::random(ShareLink::TOKEN_LENGTH);

        $this->shareLink = ShareLink::query()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->shareable instanceof Contact ? $this->shareable->vault_id : $this->shareable->id,
            'shareable_type' => $this->shareable->getMorphClass(),
            'shareable_id' => $this->shareable->id,
            'token' => hash('sha256', $this->token),
            'label' => $this->label,
            'recipient_hint' => $this->recipientHint,
            'password_hash' => $this->password === null ? null : Hash::make($this->password),
            'expires_at' => $this->expiresAt,
            'includes_files' => $this->includesFiles,
            'includes_life_events' => $this->includesLifeEvents,
            'includes_life_event_narrative' => $this->includesLifeEventNarrative,
            'includes_day_narrative' => $this->includesDayNarrative,
            'includes_relationships' => $this->includesRelationships,
            'includes_groups' => $this->includesGroups,
            'includes_sensitive_fields' => $this->includesSensitiveFields,
            'included_section_ids' => $this->includedSectionIds,
            'excluded_life_event_ids' => $this->excludedLifeEventIds,
            'view_count' => 0,
            'created_by_user_id' => $this->user->id,
        ]);

        $this->shareLink->plainToken = $this->token;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ShareLinkCreation,
            vault: $this->shareLink->vault,
            parameters: ['label' => $this->label, 'name' => $this->shareable->name],
            loggable: $this->shareLink,
        );
    }
}
