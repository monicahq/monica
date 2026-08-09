<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateAppliesToEnum;
use Carbon\Carbon;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Account $account
 * @property-read EntityType|null $entityType
 * @property-read TemplateVersion|null $publishedVersion
 */
class Template extends Model
{
    /** @use HasFactory<TemplateFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * How long a slug may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int SLUG_LENGTH = 64;

    protected $table = 'templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
     * Get the columns that are given a unique identifier of their own. The
     * primary key stays an incrementing integer, and the uuid is what the API
     * is shown.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the column a template is looked up by in a URL. The slug is readable
     * and stable, which is what a URL wants; the uuid is there for the API.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Find the template a URL points at. The slug is only unique within an
     * account, so the account has to be part of the lookup: two accounts that
     * both built a "pet" template would otherwise race for the same row.
     *
     * @param  string  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->query()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('account_id', auth()->user()?->account_id)
            ->first();
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
