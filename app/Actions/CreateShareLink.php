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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Open a private link onto one contact page or one whole vault, the way a
 * document is shared.
 *
 * What comes back is the link, with the token that goes in the URL on
 * `plainToken`. It is the only time that token exists in that form: only its
 * hash is written down, so a database dump yields no working link and a link
 * whoever made it fails to copy is lost rather than recoverable.
 *
 * Everything sensitive is out unless it is asked for here. The flags all
 * default to false, and that is not a convenience: the owner opts in, never
 * out, so a link made in a hurry shows the least rather than the most.
 *
 * The recipient never becomes a user of the account, and nothing this opens
 * leads to a write. A link is a way to let somebody read one thing, and it is
 * not the multi user product this one refuses to be.
 */
class CreateShareLink
{
    private ShareLink $shareLink;

    private string $token;

    /**
     * @param  list<int>  $excludedSectionIds
     * @param  list<int>  $excludedActivityIds
     */
    public function __construct(
        private readonly User $user,
        private readonly Contact|Vault $shareable,
        private string $label,
        private ?string $recipientHint = null,
        private readonly ?string $password = null,
        private readonly ?Carbon $expiresAt = null,
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
            'includes_activities' => $this->includesActivities,
            'includes_activity_narrative' => $this->includesActivityNarrative,
            'includes_day_narrative' => $this->includesDayNarrative,
            'includes_relationships' => $this->includesRelationships,
            'includes_groups' => $this->includesGroups,
            'includes_sensitive_fields' => $this->includesSensitiveFields,
            'excluded_section_ids' => $this->excludedSectionIds,
            'excluded_activity_ids' => $this->excludedActivityIds,
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
        )->onQueue('low');
    }
}
