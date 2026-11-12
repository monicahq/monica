<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Location;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * The locations of one account, read in one query and arranged as a tree in memory: the order the
 * screen shows them in, the path that names each one, and the address each one falls back on.
 */
class LocationTree
{
    /** @param  Collection<int, Location>  $locations */
    private function __construct(
        private readonly Collection $locations,
    ) {}

    public static function forAccount(int $accountId): self
    {
        $locations = Location::query()
            ->where('account_id', $accountId)
            ->get();

        foreach ($locations as $location) {
            $location->setRelation(
                'children',
                $locations->filter(fn (Location $child): bool => $child->parent_id === $location->id)->values(),
            );
        }

        return new self($locations);
    }

    public function isEmpty(): bool
    {
        return $this->locations->isEmpty();
    }

    public function find(int $id): ?Location
    {
        return $this->locations->firstWhere('id', $id);
    }

    /** @return SupportCollection<int, Location> */
    public function ordered(?Location $under = null): SupportCollection
    {
        return $this->childrenOf($under)
            ->flatMap(fn (Location $location): array => [$location, ...$this->ordered($location)->all()])
            ->values();
    }

    /** @return SupportCollection<int, Location> */
    public function childrenOf(?Location $parent): SupportCollection
    {
        $children = $parent instanceof Location
            ? $this->find($parent->id)->children ?? collect()
            : $this->locations->filter(fn (Location $location): bool => $location->parent_id === null);

        return LocaleCollation::sortByCollator($children, 'name')->values();
    }

    public function parentOf(Location $location): ?Location
    {
        if ($location->parent_id === null) {
            return null;
        }

        return $this->find($location->parent_id);
    }

    public function path(Location $location): string
    {
        $names = [$location->name];
        $seen = [$location->id => true];
        $ancestor = $this->parentOf($location);

        while ($ancestor instanceof Location && ! isset($seen[$ancestor->id])) {
            array_unshift($names, $ancestor->name);
            $seen[$ancestor->id] = true;
            $ancestor = $this->parentOf($ancestor);
        }

        return implode(' › ', $names);
    }

    public function addressHolder(Location $location): ?Location
    {
        $candidate = $this->find($location->id);
        $seen = [];

        while ($candidate instanceof Location && ! isset($seen[$candidate->id])) {
            if ($candidate->address !== null) {
                return $candidate;
            }

            $seen[$candidate->id] = true;
            $candidate = $this->parentOf($candidate);
        }

        return null;
    }

    public function childCount(Location $location): int
    {
        return $this->find($location->id)?->children->count() ?? 0;
    }

    /** @return list<int> */
    public function descendantIds(Location $location): array
    {
        return $this->ordered($location)
            ->map(fn (Location $descendant): int => $descendant->id)
            ->all();
    }
}
