<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\EntityTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
