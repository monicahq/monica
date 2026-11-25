<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\Entity;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\TemplateChangeItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TemplateChangeBatch
{
    /** @var Collection<int, object{id: int, structure_version_id: int}>|null */
    private ?Collection $records = null;

    /** @var array<int, true>|null */
    private ?array $blocked = null;

    /** @param list<int> $versionNumbers */
    public function __construct(
        public readonly RecordKind $kind,
        public readonly Template $source,
        public readonly array $versionNumbers,
        public readonly ?StructureVersion $target,
    ) {}

    /** @return list<int> */
    public function sourceVersionIds(): array
    {
        return $this->source->structure->versions()
            ->when($this->versionNumbers !== [], fn (Builder $query) => $query->whereIn('version_number', $this->versionNumbers))
            ->when($this->target instanceof StructureVersion, fn (Builder $query) => $query->whereKeyNot($this->target->id))
            ->pluck('id')
            ->all();
    }

    /** @return Builder<Contact>|Builder<Entity> */
    public function query(): Builder
    {
        return $this->kind->recordQuery()
            ->whereIn('structure_version_id', $this->sourceVersionIds())
            ->where('is_archived', false);
    }

    /** @return Collection<int, object{id: int, structure_version_id: int}> */
    public function records(): Collection
    {
        /** @var Collection<int, object{id: int, structure_version_id: int}> $records */
        $records = $this->records ?? $this->query()
            ->toBase()
            ->orderBy('id')
            ->get(['id', 'structure_version_id'])
            ->keyBy('id');

        return $this->records = $records
    }

    /** @return array<int, true> */
    public function blocked(): array
    {
        return $this->blocked ??= array_fill_keys(TemplateChangeItem::query()
            ->where('record_type', $this->kind->morphClass())
            ->whereNull('outcome')
            ->whereIn('record_id', $this->query()->select('id'))
            ->pluck('record_id')
            ->all(), true);
    }

    /** @return Collection<int, object{id: int, structure_version_id: int}> */
    public function movable(): Collection
    {
        $blocked = $this->blocked();

        return $this->records()->reject(fn (object $record): bool => isset($blocked[$record->id]));
    }
}
