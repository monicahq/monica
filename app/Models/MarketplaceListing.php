<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarketplaceListingStatusEnum;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\MarketplaceListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class MarketplaceListing
 *
 * Something published to the catalogue: a template or a section. It is the
 * public face of a structure somebody built.
 *
 * A section can be published on its own, with no template or page around it,
 * and that is what lowers the barrier to contributing. Any design that requires
 * wrapping a block in something bigger to share it has lost the point.
 *
 * The listing is the stable public identity, and what people install lives in
 * {@see MarketplaceListingVersion}. The split mirrors the one between a
 * template and its versions, and it is there for the same reason: what is
 * published is frozen, and what people rely on has to keep working.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $marketplace_category_id
 * @property string $listable_type
 * @property int $listable_id
 * @property string $slug
 * @property string $name
 * @property string $summary
 * @property string $description
 * @property string $locale
 * @property string $author_name
 * @property MarketplaceListingStatusEnum $status
 * @property int|null $published_version_id
 * @property string|null $rejection_reason
 * @property CarbonImmutable|null $moderated_at
 * @property string|null $moderated_by
 * @property int $install_count
 * @property CarbonImmutable|null $first_published_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read MarketplaceCategory $category
 * @property-read MarketplaceListingVersion|null $publishedVersion
 */
/*
 * A listing is looked up by its slug in a URL. The slug is unique across the
 * whole catalogue rather than within an account, since the catalogue is one
 * public place and two authors cannot both own the address a link already went
 * out to.
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'marketplace_category_id',
    'listable_type',
    'listable_id',
    'slug',
    'name',
    'summary',
    'description',
    'locale',
    'author_name',
    'status',
    'published_version_id',
    'rejection_reason',
    'moderated_at',
    'moderated_by',
    'install_count',
    'first_published_at',
])]
class MarketplaceListing extends Model
{
    /** @use HasFactory<MarketplaceListingFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * How long a slug may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int SLUG_LENGTH = 128;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'status' => MarketplaceListingStatusEnum::class,
            'moderated_at' => 'datetime',
            'install_count' => 'integer',
            'first_published_at' => 'datetime',
        ];
    }

    /**
     * Get the account that published it.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the category it was filed under, which is one of ours and the only
     * one it has.
     *
     * @return BelongsTo<MarketplaceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'marketplace_category_id');
    }

    /**
     * Get what is published, in the account of its author. It is what the
     * payload of a new version is built from, and nothing an installer ever
     * touches.
     *
     * @return MorphTo<Model, $this>
     */
    public function listable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get every state the listing has been offered in, newest first.
     *
     * @return HasMany<MarketplaceListingVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(MarketplaceListingVersion::class)->orderByDesc('version_number');
    }

    /**
     * Get the version currently offered for installation. There is none before
     * the first one is approved, and none again once the listing is withdrawn.
     *
     * @return BelongsTo<MarketplaceListingVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListingVersion::class, 'published_version_id');
    }

    /**
     * Get the copies made of it. Each is a trace of an origin and never a
     * dependency: what an installer holds is theirs.
     *
     * @return HasMany<MarketplaceInstallation, $this>
     */
    public function installations(): HasMany
    {
        return $this->hasMany(MarketplaceInstallation::class);
    }
}
