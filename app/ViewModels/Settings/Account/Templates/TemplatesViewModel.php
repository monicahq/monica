<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything the account has to describe a record with, split into what
 * somebody here built and what the product supplied. A supplied template
 * cannot be edited in place, so which side of the line it falls on is what
 * tells somebody whether to open it or copy it first.
 *
 * The screen says nothing about how many records use a template. Records and
 * vault defaults are later models, and a column reading `no records` on every
 * row for months would be read as a fault rather than as an absence.
 */
class TemplatesViewModel
{
    public const string FILTER_ALL = 'all';

    public const string FILTER_DRAFTS = 'drafts';

    public const string FILTER_PUBLISHED = 'published';

    public const string FILTER_SUPPLIED = 'supplied';

    public function __construct(
        private readonly User $user,
        private readonly string $query = '',
        private readonly string $filter = self::FILTER_ALL,
    ) {}

    /**
     * The templates somebody here built, which are theirs to change, version
     * and publish.
     *
     * @return array<int, array<string, mixed>>
     */
    public function ownTemplates(): array
    {
        return $this->matching()->reject(fn (Template $template): bool => $template->is_system)
            ->map(fn (Template $template): array => $this->row($template))
            ->values()
            ->all();
    }

    /**
     * The templates the product supplied, which work out of the box and are
     * copied rather than edited.
     *
     * @return array<int, array<string, mixed>>
     */
    public function suppliedTemplates(): array
    {
        return $this->matching()->filter(fn (Template $template): bool => $template->is_system)
            ->map(fn (Template $template): array => $this->row($template))
            ->values()
            ->all();
    }

    /**
     * The chips that narrow the list, each knowing whether it is the one in
     * force.
     *
     * @return array<int, array{key: string, label: string, isCurrent: bool}>
     */
    public function filters(): array
    {
        $labels = [
            self::FILTER_ALL => __('All'),
            self::FILTER_DRAFTS => __('Drafts'),
            self::FILTER_PUBLISHED => __('Published'),
            self::FILTER_SUPPLIED => __('Supplied'),
        ];

        return array_map(fn (string $key): array => [
            'key' => $key,
            'label' => $labels[$key],
            'isCurrent' => $key === $this->filter(),
        ], array_keys($labels));
    }

    /**
     * What was typed in the box that narrows the list, so the field keeps it
     * after the list has been redrawn.
     */
    public function query(): string
    {
        return $this->query;
    }

    public function filter(): string
    {
        return in_array($this->filter, [self::FILTER_ALL, self::FILTER_DRAFTS, self::FILTER_PUBLISHED, self::FILTER_SUPPLIED], true)
            ? $this->filter
            : self::FILTER_ALL;
    }

    /**
     * How much of the list is showing, so nobody has to count the rows to know
     * whether a filter hid something.
     */
    public function summary(): string
    {
        $showing = $this->matching()->count();
        $total = $this->all()->count();

        if ($showing === $total) {
            return $total === 1 ? __('1 template') : __(':count templates', ['count' => $total]);
        }

        return __(':showing of :total templates', ['showing' => $showing, 'total' => $total]);
    }

    public function user(): User
    {
        return $this->user;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Template $template): array
    {
        $shown = $template->publishedVersion ?? $template->versions->first();
        $draft = $template->versions->firstWhere('status', VersionStatusEnum::Draft);

        return [
            'slug' => $template->slug,
            'name' => $template->name,
            'description' => $template->description,
            'isSystem' => $template->is_system,
            'isActive' => $template->is_active,
            'hasDraft' => $draft !== null,
            'appliesTo' => $template->applies_to,
            'entityTypeName' => $template->entityType?->name_plural,
            'sectionLabel' => self::sectionLabel($shown?->assembly->count() ?? 0),
            'updatedLabel' => __('Updated :date', ['date' => $template->updated_at?->isoFormat('LL') ?? '']),
            'versionLabel' => $shown === null ? '—' : 'v'.$shown->version_number,
            'versionSubLabel' => self::versionSubLabel($template, $shown),
            'openLabel' => $draft !== null ? __('Continue the draft') : __('Open'),
        ];
    }

    /**
     * How many sections a template carries, in words.
     */
    private static function sectionLabel(int $count): string
    {
        if ($count === 0) {
            return __('no sections yet');
        }

        return $count === 1 ? __('1 section') : __(':count sections', ['count' => $count]);
    }

    /**
     * The line under the version number, which says what state that version is
     * in and, for a draft, what it was made from.
     */
    private static function versionSubLabel(Template $template, ?TemplateVersion $shown): string
    {
        if ($shown === null) {
            return __('never published');
        }

        if ($shown->status === VersionStatusEnum::Published) {
            return __('published');
        }

        if ($shown->status === VersionStatusEnum::Retired) {
            return __('retired');
        }

        $previous = $template->versions->firstWhere('id', $shown->previous_version_id);

        return $previous === null
            ? __('draft')
            : __('draft, based on v:version', ['version' => $previous->version_number]);
    }

    /**
     * @return Collection<int, Template>
     */
    private function matching(): Collection
    {
        $query = mb_strtolower(mb_trim($this->query));

        return $this->all()
            ->filter(function (Template $template) use ($query): bool {
                if ($query === '') {
                    return true;
                }

                return str_contains(mb_strtolower($template->name), $query)
                    || str_contains($template->slug, $query);
            })
            ->filter(fn (Template $template): bool => match ($this->filter()) {
                self::FILTER_DRAFTS => $template->versions->contains('status', VersionStatusEnum::Draft),
                self::FILTER_PUBLISHED => $template->published_version_id !== null,
                self::FILTER_SUPPLIED => $template->is_system,
                default => true,
            });
    }

    /**
     * @return Collection<int, Template>
     */
    private function all(): Collection
    {
        return once(fn (): Collection => $this->templates()->get());
    }

    /**
     * @return Builder<Template>
     */
    private function templates(): Builder
    {
        return Template::query()
            ->where('account_id', $this->user->account_id)
            ->with(['entityType', 'versions.assembly', 'publishedVersion.assembly'])
            ->orderBy('position');
    }
}
