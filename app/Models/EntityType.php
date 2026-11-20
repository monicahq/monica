<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\EntityTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Override;

/**
 * Class EntityType
 *
 * A kind of object the user invented: Animal, Company, Recipe, Wine bottle.
 * It is what somebody reaches for when nothing the product ships with fits.
 *
 * The type is only the noun. What one of them holds is described separately by
 * a template, and the same type may have several of them: the family vault and
 * a professional breeding vault both handle Animals, at completely different
 * levels of detail.
 *
 * Both a singular and a plural name are stored rather than one being derived
 * from the other, because deriving does not survive translation.
 *
 * The type belongs to the account and its records belong to vaults, so a type
 * declared once is visible everywhere. Whether it is actually used in a given
 * vault is decided there, by whether a default template is set for it.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name_singular
 * @property string $name_plural
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string $colour
 * @property int $position
 * @property bool $is_active
 * @property int $entity_count
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 */
/*
 * A type is looked up by its slug in a URL. The slug is readable and stable,
 * which is what a URL wants; the uuid is there for the API, which wants
 * something nobody has to have seen before.
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'name_singular',
    'name_plural',
    'slug',
    'description',
    'icon',
    'colour',
    'position',
    'is_active',
    'entity_count',
])]
class EntityType extends Model
{
    /** @use HasFactory<EntityTypeFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;
    use SoftDeletes;

    /**
     * The colours a type may be drawn in, and the one it is given when whoever
     * created it did not pick. A short list rather than a free colour, so that
     * a navigation of a dozen types stays readable against both themes.
     *
     * @var list<string>
     */
    public const array COLOURS = ['#3f5fd0', '#4f8a49', '#9a6a24', '#9a3f2b', '#5d5f96', '#1d1d20'];

    public const string DEFAULT_COLOUR = '#3f5fd0';

    /**
     * The icons a type may be marked with. They are names rather than markup,
     * so the drawing lives in one component and a type stores a word.
     *
     * @var list<string>
     */
    public const array ICONS = ['profile', 'logs', 'security', 'preferences', 'key', 'device', 'info', 'activity', 'mail', 'sun', 'moon', 'check', 'pet'];

    public const string DEFAULT_ICON = 'profile';

    /**
     * How long a slug may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int SLUG_LENGTH = 64;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
            'entity_count' => 'integer',
        ];
    }

    /**
     * Get the account the type belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the records of this kind, across every vault of the account.
     *
     * @return HasMany<Entity, $this>
     */
    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }

    /**
     * Get the template each vault starts a record of this kind from.
     *
     * @return HasMany<VaultDefaultTemplate, $this>
     */
    public function vaultDefaultTemplates(): HasMany
    {
        return $this->hasMany(VaultDefaultTemplate::class);
    }

    /**
     * Get the published version a new record of this kind starts from in the vault, which is null
     * when the vault has no default for it or when that default can no longer be used: a version
     * that is not published, or a template that was turned off. The type being turned off is a
     * separate question, asked by the policy.
     */
    public function defaultVersionIn(Vault $vault): ?StructureVersion
    {
        return $this->vaultDefaultTemplates()
            ->where('vault_id', $vault->id)
            ->where('applies_to', StructureAppliesToEnum::Entity)
            ->whereHas('structureVersion', function (Builder $query): void {
                $query->where('status', VersionStatusEnum::Published)
                    ->whereHas('structure', function (Builder $query): void {
                        $query->whereHasMorph('owner', [Template::class], function (Builder $query): void {
                            $query->where('is_active', true);
                        });
                    });
            })
            ->first()
            ?->structureVersion;
    }

    /**
     * Get the published versions a new record of this kind may start from in the vault, one per
     * active template of the kind, the vault's default first and the others in the order the
     * account keeps its templates. This is what the form that adds a record offers.
     *
     * @return Collection<int, StructureVersion>
     */
    public function versionsOnOfferIn(Vault $vault): Collection
    {
        $default = $this->defaultVersionIn($vault);

        $versions = StructureVersion::query()
            ->where('account_id', $this->account_id)
            ->where('status', VersionStatusEnum::Published)
            ->whereHas('structure', function (Builder $query): void {
                $query->where('entity_type_id', $this->id)
                    ->whereColumn('published_version_id', 'structure_versions.id')
                    ->whereHasMorph('owner', [Template::class], function (Builder $query): void {
                        $query->where('is_active', true);
                    });
            })
            ->with('structure.owner')
            ->get()
            ->sortBy(fn (StructureVersion $version): int => $version->structure->owner->position ?? 0)
            ->values();

        if (! $default instanceof StructureVersion) {
            return $versions;
        }

        return $versions
            ->reject(fn (StructureVersion $version): bool => $version->id === $default->id)
            ->prepend($default)
            ->values();
    }

    /**
     * Scope the types a vault is set up for: the type is active and the vault starts its records
     * from an active, published template. This is what the vault's navigation lists.
     *
     * @param  Builder<EntityType>  $query
     */
    public function scopeConfiguredInVault(Builder $query, Vault $vault): void
    {
        $query->where('is_active', true)
            ->whereHas('vaultDefaultTemplates', function (Builder $query) use ($vault): void {
                $query->where('vault_id', $vault->id)
                    ->where('applies_to', StructureAppliesToEnum::Entity)
                    ->whereHas('structureVersion', function (Builder $query): void {
                        $query->where('status', VersionStatusEnum::Published)
                            ->whereHas('structure', function (Builder $query): void {
                                $query->whereHasMorph('owner', [Template::class], function (Builder $query): void {
                                    $query->where('is_active', true);
                                });
                            });
                    });
            });
    }

    /**
     * Scope the types whose records a vault can still be reached for. A type is reachable when the
     * vault is set up for it, or when the vault still holds a record of it, archived or not, so that
     * turning a type off never hides what was written.
     *
     * @param  Builder<EntityType>  $query
     */
    public function scopeVisibleInVault(Builder $query, Vault $vault): void
    {
        $query->where(function (Builder $query) use ($vault): void {
            $query
                ->where(function (Builder $query) use ($vault): void {
                    $query->configuredInVault($vault);
                })
                ->orWhereHas('entities', function (Builder $query) use ($vault): void {
                    $query->where('vault_id', $vault->id);
                });
        });
    }
}
