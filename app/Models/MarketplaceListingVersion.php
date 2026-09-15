<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarketplaceVersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\CarbonImmutable;
use Database\Factories\MarketplaceListingVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class MarketplaceListingVersion
 *
 * One published state of a listing, with the payload somebody installs and the
 * change note they read before deciding whether to update.
 *
 * It is separate from the listing for the same reason a structure version is
 * separate from a template: what is published is frozen, and what people rely
 * on has to keep working. It is also what makes the update path possible. When
 * an author publishes a new version, everybody who installed the previous one
 * is told about it with the change note, and each of them decides whether to
 * apply it or stay where they are. There is never a forced synchronisation.
 * That is the single most important rule of the marketplace.
 *
 * The payload is self contained. Installing must never need anything from the
 * account of the author, because installing is copying and the copy has to
 * stand alone.
 *
 * @property int $id
 * @property int $marketplace_listing_id
 * @property int $version_number
 * @property array<string, mixed> $payload
 * @property string|null $change_note
 * @property MarketplaceVersionStatusEnum $status
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $approved_at
 * @property CarbonImmutable|null $withdrawn_at
 * @property int|null $previous_version_id
 * @property int $install_count
 * @property string $checksum
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read MarketplaceListing $listing
 * @property-read MarketplaceListingVersion|null $previousVersion
 */
#[Fillable([
    'marketplace_listing_id',
    'version_number',
    'payload',
    'change_note',
    'status',
    'rejection_reason',
    'approved_at',
    'withdrawn_at',
    'previous_version_id',
    'install_count',
    'checksum',
])]
class MarketplaceListingVersion extends Model
{
    /** @use HasFactory<MarketplaceListingVersionFactory> */
    use HasFactory;

    /**
     * What approval freezes. Everything else on the row describes where the
     * version stands rather than what it contains: it may still be withdrawn,
     * and the count of accounts holding a copy of it still moves.
     *
     * @var list<string>
     */
    private const array FROZEN = [
        'marketplace_listing_id',
        'version_number',
        'payload',
        'change_note',
        'previous_version_id',
        'checksum',
    ];

    /**
     * Guard the freeze here rather than in each action that could reach it. An
     * approved version is what an install checked its checksum against, so a
     * payload that could still change afterwards would make that check a lie.
     */
    #[Override]
    protected static function booted(): void
    {
        static::updating(function (MarketplaceListingVersion $version): void {
            if ($version->getRawOriginal('status') !== MarketplaceVersionStatusEnum::Approved->value) {
                return;
            }

            if ($version->isDirty(self::FROZEN)) {
                throw new PublishedVersionIsFrozen("Version {$version->version_number} has been approved. Submit a new one instead.");
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'payload' => 'array',
            'status' => MarketplaceVersionStatusEnum::class,
            'approved_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'install_count' => 'integer',
        ];
    }

    /**
     * Get the listing this is a version of.
     *
     * @return BelongsTo<MarketplaceListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'marketplace_listing_id');
    }

    /**
     * Get the version this one was built from, which is null for the first.
     *
     * @return BelongsTo<MarketplaceListingVersion, $this>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListingVersion::class, 'previous_version_id');
    }

    /**
     * Get the copies made of this precise version. They are what the update
     * notification is sent to when a newer one is approved.
     *
     * @return HasMany<MarketplaceInstallation, $this>
     */
    public function installations(): HasMany
    {
        return $this->hasMany(MarketplaceInstallation::class);
    }
}
