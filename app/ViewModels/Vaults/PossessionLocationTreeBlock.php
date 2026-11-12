<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\VaultColourEnum;
use App\Helpers\LocationTree;
use App\Models\Location;
use App\Models\Possession;
use App\Models\Vault;
use Illuminate\Support\Collection;

/**
 * The locations of the account beside All possessions, each counting the possessions of this vault
 * kept there or inside it. A top level location gives its colour to every location inside it.
 */
class PossessionLocationTreeBlock
{
    /** @param  Collection<int, Possession>  $possessions */
    public function __construct(
        private readonly Vault $vault,
        private readonly LocationTree $tree,
        private readonly Collection $possessions,
        private readonly ?Location $current,
    ) {}

    /** @return list<array{name: string, url: string, depth: int, icon: string, colour: string, count: int, isCurrent: bool}> */
    public function nodes(): array
    {
        return once(function (): array {
            $nodes = [[
                'name' => __('All locations'),
                'url' => route('possessions.index', $this->vault),
                'depth' => 0,
                'icon' => 'layers',
                'colour' => 'var(--muted)',
                'count' => $this->possessions->count(),
                'isCurrent' => ! $this->current instanceof Location,
            ]];

            foreach ($this->tree->ordered() as $location) {
                $depth = $this->depthOf($location);

                $nodes[] = [
                    'name' => $location->name,
                    'url' => route('possessions.locations.show', ['vault' => $this->vault, 'location' => $location]),
                    'depth' => $depth,
                    'icon' => $depth === 0 ? 'place' : 'chevron-right',
                    'colour' => $this->colourOf($location),
                    'count' => $this->countIn($location),
                    'isCurrent' => $this->current?->id === $location->id,
                ];
            }

            return $nodes;
        });
    }

    public function manageUrl(): string
    {
        return route('settings.locations.index');
    }

    public function colourOf(Location $location): string
    {
        $topLevelIds = $this->tree->childrenOf(null)
            ->map(fn (Location $topLevel): int => $topLevel->id)
            ->values()
            ->all();

        $index = (int) array_search($this->topLevelOf($location)->id, $topLevelIds, true);
        $palette = VaultColourEnum::cases();

        return $palette[$index % count($palette)]->value;
    }

    public function countIn(Location $location): int
    {
        $ids = [$location->id, ...$this->tree->descendantIds($location)];

        return $this->possessions
            ->filter(fn (Possession $possession): bool => in_array($possession->current_location_id, $ids, true))
            ->count();
    }

    private function depthOf(Location $location): int
    {
        $depth = 0;
        $parent = $this->tree->parentOf($location);

        while ($parent instanceof Location && $depth < 50) {
            $depth++;
            $parent = $this->tree->parentOf($parent);
        }

        return $depth;
    }

    private function topLevelOf(Location $location): Location
    {
        $topLevel = $location;
        $parent = $this->tree->parentOf($location);
        $steps = 0;

        while ($parent instanceof Location && $steps < 50) {
            $topLevel = $parent;
            $parent = $this->tree->parentOf($parent);
            $steps++;
        }

        return $topLevel;
    }
}
