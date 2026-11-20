<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Models\Concerns\HasUuids;
use App\Models\Concerns\ResolvesModelInAccount;
use Carbon\CarbonImmutable;
use Database\Factories\TemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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
 * @property string $name
 * @property string|null $name_translation_key
 * @property string|null $description
 * @property string $slug
 * @property bool $is_system
 * @property bool $is_active
 * @property string $locale
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Account $account
 * @property-read Structure|null $structure
 * @property-read StructureVersion|null $publishedVersion
 */
/*
 * A template is looked up by its slug in a URL. The slug is readable and
 * stable, which is what a URL wants; the uuid is there for the API.
 */
#[RouteKey('slug')]
#[Fillable([
    'account_id',
    'name',
    'name_translation_key',
    'description',
    'slug',
    'is_system',
    'is_active',
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
     * Get the field structure the template owns. A template has exactly one and
     * it is created with the template, so this is null only for a row somebody
     * built by hand.
     *
     * @return MorphOne<Structure, $this>
     */
    public function structure(): MorphOne
    {
        return $this->morphOne(Structure::class, 'owner');
    }

    /**
     * Get every state the template has been in, newest first.
     *
     * The versions belong to the structure rather than to the template, since
     * a life event type has them too. This reaches them through it, so that a
     * screen showing a template's history does not have to know the engine was
     * ever separated from it.
     *
     * @return HasManyThrough<StructureVersion, Structure, $this>
     */
    public function versions(): HasManyThrough
    {
        return $this->hasManyThrough(
            StructureVersion::class,
            Structure::class,
            'owner_id',
            'structure_id',
            'id',
            'id',
        )->where('structures.owner_type', self::class)
            ->orderByDesc('version_number');
    }

    /**
     * Get the version offered by default when the template is applied. There is
     * none while only drafts exist, which is a template somebody is still
     * designing.
     *
     * @return HasOneThrough<StructureVersion, Structure, $this>
     */
    public function publishedVersion(): HasOneThrough
    {
        return $this->hasOneThrough(
            StructureVersion::class,
            Structure::class,
            'owner_id',
            'id',
            'id',
            'published_version_id',
        )->where('structures.owner_type', self::class);
    }

    /**
     * Find the template a new record of one kind starts from when nobody picks
     * one: what a new vault is given as its default, and what a vault falls back
     * to when the template it used is deleted.
     *
     * Only a template with a published version qualifies. An active one comes
     * before one turned off, then the templates the product supplied in the order
     * of DefaultTemplateEnum, which puts Basic contact and Basic animal first,
     * then any other system template, then the account's own in the order of the
     * picker. A turned off template is
     * still returned when nothing else is left, because a vault with no default
     * refuses every new record.
     */
    public static function startingPointFor(
        int $accountId,
        StructureAppliesToEnum $appliesTo,
        ?int $entityTypeId = null,
        ?int $exceptTemplateId = null,
    ): ?self {
        $suppliedOrder = array_map(fn (DefaultTemplateEnum $supplied): string => $supplied->value, DefaultTemplateEnum::cases());

        return self::query()
            ->where('account_id', $accountId)
            ->when($exceptTemplateId !== null, fn (Builder $query) => $query->whereKeyNot($exceptTemplateId))
            ->whereHas('structure', function (Builder $query) use ($appliesTo, $entityTypeId): void {
                $query->where('applies_to', $appliesTo)
                    ->where('entity_type_id', $entityTypeId)
                    ->whereNotNull('published_version_id');
            })
            ->with('structure')
            ->get()
            ->sortBy(fn (self $template): array => [
                $template->is_active ? 0 : 1,
                match (true) {
                    $template->is_system && in_array($template->slug, $suppliedOrder, true) => (int) array_search($template->slug, $suppliedOrder, true),
                    $template->is_system => count($suppliedOrder),
                    default => count($suppliedOrder) + 1,
                },
                $template->position,
            ])
            ->first();
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
