<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateAppliesToEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

/**
 * Class Template
 *
 * The definition of a record: what a contact page shows, what an Animal holds.
 * It is what gets applied to a record, and what decides what appears on it.
 *
 * The template itself holds no fields. It is a container with an identity, and
 * what it holds is versions. The split is the whole answer to the question the
 * personalisation engine opens: if everybody can change structures, how do
 * records stay stable? A record points at a precise version rather than at the
 * latest one, so the stable identity has to live somewhere, and it lives here.
 *
 * A template is private. Nothing about it is offered to anybody else until
 * somebody deliberately publishes it, which is a separate act.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property TemplateAppliesToEnum $applies_to
 * @property int|null $entity_type_id
 * @property string $name
 * @property string|null $name_translation_key
 * @property string|null $description
 * @property string $slug
 * @property bool $is_system
 * @property bool $is_active
 * @property int|null $published_version_id
 * @property string $locale
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read EntityType|null $entityType
 * @property-read TemplateVersion|null $publishedVersion
 */
/*
 * A template is looked up by its slug in a URL. The slug is readable and
 * stable, which is what a URL wants; the uuid is there for the API.
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'applies_to',
    'entity_type_id',
    'name',
    'name_translation_key',
    'description',
    'slug',
    'is_system',
    'is_active',
    'published_version_id',
    'locale',
    'position',
])]
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    use HasUuids;
    use ResolvesModelInAccount;
    use SoftDeletes;

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
            'applies_to' => TemplateAppliesToEnum::class,
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Get the account the template belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the kind of record the template describes, which only a template
     * applying to an entity has.
     *
     * @return BelongsTo<EntityType, $this>
     */
    public function entityType(): BelongsTo
    {
        return $this->belongsTo(EntityType::class);
    }

    /**
     * Get every state the template has been in, newest first.
     *
     * @return HasMany<TemplateVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class)->orderByDesc('version_number');
    }

    /**
     * Get the version offered by default when the template is applied. There is
     * none while only drafts exist, which is a template somebody is still
     * designing.
     *
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class, 'published_version_id');
    }

    /**
     * Get what the template is called. One the product supplied has no name of
     * its own until somebody gives it one, and reads in the language of whoever
     * is looking until then.
     *
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->name_translation_key),
        );
    }
}
