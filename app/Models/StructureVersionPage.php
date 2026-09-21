<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\PublishedVersionIsFrozen;
use App\Models\Concerns\HasUuids;
use Carbon\CarbonImmutable;
use Database\Factories\StructureVersionPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * Class StructureVersionPage
 *
 * One named and ordered page inside a structure version, and the missing layer
 * between the version and the sections placed in it. A record with several of
 * them reads as tabs.
 *
 * A page is deliberately not a thing of its own beside a template or a section.
 * It cannot be reused or published on its own, and its identity, its label and
 * its position describe one precise structure version. Drafting from a version
 * copies its pages, so a published version and a draft never share a row either
 * of them could move.
 *
 * The key is what makes a page addressable. It is settled once, travels in
 * record URLs, is copied into every later draft, and renaming the label leaves
 * it alone: a link that already went out has to keep working.
 *
 * @property int $id
 * @property string $uuid
 * @property int $account_id
 * @property int $structure_version_id
 * @property string $key
 * @property string $label
 * @property string|null $label_translation_key
 * @property int $position
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Account $account
 * @property-read StructureVersion $structureVersion
 */
#[RouteKey('uuid')]
#[Fillable([
    'account_id',
    'structure_version_id',
    'key',
    'label',
    'label_translation_key',
    'position',
])]
class StructureVersionPage extends Model
{
    /** @use HasFactory<StructureVersionPageFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * How long a key may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int KEY_LENGTH = 64;

    /**
     * The key the first page of a structure version is given. Its label is
     * stored as a translation key like any other supplied name, so the tab
     * reads in the language of whoever is looking until they rename it.
     */
    public const string SUPPLIED_KEY = 'general';

    /**
     * A page is as frozen as the version it belongs to. Once the template
     * version has been published, no page may be added to it, renamed, moved
     * or taken out of it, since all four change what an already published
     * version renders.
     */
    #[Override]
    protected static function booted(): void
    {
        static::saving(function (StructureVersionPage $page): void {
            $page->refuseWhenTheStructureIsPublished();
        });

        static::deleting(function (StructureVersionPage $page): void {
            $page->refuseWhenTheStructureIsPublished();
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
            'position' => 'integer',
        ];
    }

    /**
     * Whether this page is one the given user may reach through the given owner,
     * which is a template or a life event type.
     *
     * A page is addressed under the thing it sits on, and neither half can be
     * trusted on its own: the account has to match, and the page has to be on a
     * version of the structure the URL actually names.
     */
    public function isReachableThrough(User $user, LifeEventType|Template $owner): bool
    {
        return $this->account_id === $user->account_id
            && $this->structureVersion->structure->owner_type === $owner::class
            && $this->structureVersion->structure->owner_id === $owner->id;
    }

    /**
     * Get the account the page belongs to.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the structure version the page is part of.
     *
     * @return BelongsTo<StructureVersion, $this>
     */
    public function structureVersion(): BelongsTo
    {
        return $this->belongsTo(StructureVersion::class);
    }

    /**
     * Get the sections placed on the page, in the order they are read in.
     *
     * @return HasMany<StructureVersionSection, $this>
     */
    public function assembly(): HasMany
    {
        return $this->hasMany(StructureVersionSection::class)->orderBy('position');
    }

    /**
     * What the tab says. A page the product supplied carries a translation key
     * rather than words, so it reads in the language of whoever is looking
     * until somebody renames it.
     *
     * @return Attribute<string, string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => $value ?? __((string) $this->label_translation_key),
        );
    }

    private function refuseWhenTheStructureIsPublished(): void
    {
        $version = $this->structureVersion;

        if ($version !== null && ! $version->status->isEditable()) {
            throw new PublishedVersionIsFrozen("Version {$version->version_number} of this structure has been published. Make a draft from it instead.");
        }
    }
}
