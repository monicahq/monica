<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ShareLinkFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class ShareLink
 *
 * A private link giving read access to one contact page or one vault, the way a
 * document is shared.
 *
 * It is not collaborative access to the application. Whoever opens it needs no
 * account and never becomes a user of the account they are looking into, and
 * sharing a vault brings back none of what the product refused: no fine grained
 * permissions, no editing by several people, no collective ownership. Nothing
 * built on this may ever lead to a write.
 *
 * Everything sensitive is out until the owner says otherwise. Every inclusion
 * here is false to begin with, and the two exclusion lists tighten the result
 * further, section by section and activity by activity.
 *
 * Only the hash of the token is kept, as for a magic link, so a database dump
 * yields no working link. The plain token exists for the length of the request
 * that mints it, on {@see ShareLink::$plainToken}, and never again.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $vault_id
 * @property string $shareable_type
 * @property int $shareable_id
 * @property string $token
 * @property string $label
 * @property string|null $recipient_hint
 * @property string|null $password_hash
 * @property Carbon|null $expires_at
 * @property Carbon|null $revoked_at
 * @property bool $includes_files
 * @property bool $includes_activities
 * @property bool $includes_activity_narrative
 * @property bool $includes_day_narrative
 * @property bool $includes_relationships
 * @property bool $includes_groups
 * @property bool $includes_sensitive_fields
 * @property array<array-key, mixed> $excluded_section_ids
 * @property array<array-key, mixed> $excluded_activity_ids
 * @property int $view_count
 * @property Carbon|null $last_viewed_at
 * @property int|null $created_by_user_id
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read Vault $vault
 * @property-read Contact|Vault $shareable
 * @property-read User|null $createdBy
 */
class ShareLink extends Model
{
    /** @use HasFactory<ShareLinkFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * How long the token in the URL is before it is hashed. It is the only
     * secret protecting what the link opens, so it is long enough that guessing
     * one is not a strategy.
     */
    public const int TOKEN_LENGTH = 64;

    /**
     * The token that goes in the URL, in plain text. It is set the moment the
     * link is made and is never written down, so a link whoever made it fails
     * to copy is lost rather than recoverable.
     */
    public ?string $plainToken = null;

    protected $table = 'share_links';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'vault_id',
        'shareable_type',
        'shareable_id',
        'token',
        'label',
        'recipient_hint',
        'password_hash',
        'expires_at',
        'revoked_at',
        'includes_files',
        'includes_activities',
        'includes_activity_narrative',
        'includes_day_narrative',
        'includes_relationships',
        'includes_groups',
        'includes_sensitive_fields',
        'excluded_section_ids',
        'excluded_activity_ids',
        'view_count',
        'last_viewed_at',
        'created_by_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'includes_files' => 'boolean',
            'includes_activities' => 'boolean',
            'includes_activity_narrative' => 'boolean',
            'includes_day_narrative' => 'boolean',
            'includes_relationships' => 'boolean',
            'includes_groups' => 'boolean',
            'includes_sensitive_fields' => 'boolean',
            'excluded_section_ids' => 'array',
            'excluded_activity_ids' => 'array',
            'view_count' => 'integer',
            'last_viewed_at' => 'datetime',
        ];
    }

    /**
     * Get the columns that are given a unique identifier of their own. The
     * primary key stays an incrementing integer, and the uuid is what the owner
     * sees. It is not the token: the address of the screen managing a link must
     * not be the secret that opens it.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the column a link is looked up by in a URL, on the side of the owner.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the account that created the link.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the vault being shared, or the vault of the contact being shared.
     *
     * @return BelongsTo<Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get what is shared, which is a contact or a vault.
     *
     * @return MorphTo<Model, $this>
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created the link.
     *
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get every opening of the link, newest first. Revoking one keeps them:
     * knowing whether something was read once or forty times is what makes
     * sharing feel safe enough to do.
     *
     * @return HasMany<ShareLinkVisit, $this>
     */
    public function visits(): HasMany
    {
        return $this->hasMany(ShareLinkVisit::class)->latest('viewed_at');
    }

    /**
     * Get whether the link still opens anything.
     */
    public function isUsable(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Get whether a password has to be given before it opens.
     */
    public function isProtectedByAPassword(): bool
    {
        return $this->password_hash !== null;
    }
}
