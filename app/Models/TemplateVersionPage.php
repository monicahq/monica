<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\PublishedVersionIsFrozen;
use Carbon\Carbon;
use Database\Factories\TemplateVersionPageFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class TemplateVersionPage
 *
 * One named and ordered page inside a template version, and the missing layer
 * between the version and the sections placed in it. A record with several of
 * them reads as tabs.
 *
 * A page is deliberately not a thing of its own beside a template or a section.
 * It cannot be reused or published on its own, and its identity, its label and
 * its position describe one precise template version. Drafting from a version
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
 * @property int $template_version_id
 * @property string $key
 * @property string $label
 * @property string|null $label_translation_key
 * @property int $position
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read Account $account
 * @property-read TemplateVersion $templateVersion
 */
class TemplateVersionPage extends Model
{
    /** @use HasFactory<TemplateVersionPageFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * How long a key may be, which the column enforces and the action that
     * builds one has to respect while it is still making it unique.
     */
    public const int KEY_LENGTH = 64;

    /**
     * The key the first page of a template version is given. Its label is
     * stored as a translation key like any other supplied name, so the tab
     * reads in the language of whoever is looking until they rename it.
     */
    public const string SUPPLIED_KEY = 'general';

    protected $table = 'template_version_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'template_version_id',
        'key',
        'label',
        'label_translation_key',
        'position',
    ];

    /**
     * A page is as frozen as the version it belongs to. Once the template
     * version has been published, no page may be added to it, renamed, moved
     * or taken out of it, since all four change what an already published
     * version renders.
     */
    protected static function booted(): void
    {
        static::saving(function (TemplateVersionPage $page): void {
            $page->refuseWhenTheTemplateIsPublished();
        });

        static::deleting(function (TemplateVersionPage $page): void {
            $page->refuseWhenTheTemplateIsPublished();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * Get the column a page is looked up by in a URL.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Whether this page is one the given user may reach through the given
     * template.
     *
     * A page is addressed under the template it sits on, and neither half can
     * be trusted on its own: the account has to match, and the page has to be
     * on a version of the template the URL actually names.
     */
    public function isReachableThrough(User $user, Template $template): bool
    {
        return $this->account_id === $user->account_id
            && $this->templateVersion->template_id === $template->id;
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
     * Get the template version the page is part of.
     *
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }

    /**
     * Get the sections placed on the page, in the order they are read in.
     *
     * @return HasMany<TemplateVersionSection, $this>
     */
    public function assembly(): HasMany
    {
        return $this->hasMany(TemplateVersionSection::class)->orderBy('position');
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

    private function refuseWhenTheTemplateIsPublished(): void
    {
        $version = $this->templateVersion;

        if ($version !== null && ! $version->status->isEditable()) {
            throw new PublishedVersionIsFrozen("Version {$version->version_number} of this template has been published. Make a draft from it instead.");
        }
    }
}
