<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Section
 *
 * A reusable thematic section: "Address", "Social networks", "Medical follow up".
 * A template is an assembly of these, in the order somebody chose.
 *
 * A section is its own object rather than a grouping inside a template for one
 * reason: so a single section can be shared without dragging a whole page around
 * it. Somebody who built a good medical follow up section can offer that section
 * alone, and that is what makes the three level split worth its weight.
 *
 * A section is not owned by a template. The same section can appear in several
 * templates, and in several versions of the same template, at a different
 * position each time. Where it sits is a property of the assembly, not of the
 * section, which is why there is no position here.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property string $name
 * @property string|null $name_translation_key
 * @property string|null $description
 * @property string $slug
 * @property string|null $icon
 * @property bool $is_system
 * @property bool $is_active
 * @property int|null $published_version_id
 * @property string $locale
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Account $account
 * @property-read SectionVersion|null $publishedVersion
 */
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    /**
     * How long a slug may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int SLUG_LENGTH = 64;

    protected $table = 'sections';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'name_translation_key',
        'description',
        'slug',
        'icon',
        'is_system',
        'is_active',
        'published_version_id',
        'locale',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the columns that are given a unique identifier of their own.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the column a section is looked up by in a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Find the section a URL points at. The slug is only unique within an
     * account, so the account has to be part of the lookup.
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
     * Get the account the section belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get every state the section has been in, newest first.
     *
     * @return HasMany<SectionVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SectionVersion::class)->orderByDesc('version_number');
    }

    /**
     * Get the version offered by default when the section is added to a template.
     *
     * @return BelongsTo<SectionVersion, $this>
     */
    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(SectionVersion::class, 'published_version_id');
    }

    /**
     * Get what the section is called. One the product supplied has no name of
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
