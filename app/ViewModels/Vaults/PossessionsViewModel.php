<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\LocaleCollation;
use App\Helpers\LocationTree;
use App\Helpers\PossessionValueFormatter;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;

/**
 * All possessions of a vault, browsed by the location they are kept at: everything, or one location
 * and every location inside it. While the vault holds none, the screen explains what a possession is
 * and offers three ways in: the form, the account's types, and its locations.
 */
class PossessionsViewModel
{
    public const int TYPE_LIMIT = 8;

    public const int LOCATION_NAME_LIMIT = 3;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?Location $location = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function isEmpty(): bool
    {
        return once(fn (): bool => ! Possession::query()->where('vault_id', $this->vault->id)->exists());
    }

    public function newUrl(): string
    {
        return route('possessions.new', $this->vault);
    }

    public function typesSettingsUrl(): string
    {
        return route('settings.possessionTypes.index');
    }

    public function locationsSettingsUrl(): string
    {
        return route('settings.locations.index');
    }

    /** @return list<array{name: string, colour: string, tint: string, icon: string, fields: string, newUrl: string}> */
    public function types(): array
    {
        return once(function (): array {
            $types = PossessionType::query()
                ->where('account_id', $this->user->account_id)
                ->with(['groups.fields', 'standaloneFields'])
                ->get();

            return LocaleCollation::sortByCollator($types, 'name')
                ->take(self::TYPE_LIMIT)
                ->values()
                ->map(fn (PossessionType $type): array => [
                    'name' => $type->name,
                    'colour' => $type->colour,
                    'tint' => VaultColourEnum::tintFor($type->colour),
                    'icon' => $type->icon ?? Section::DEFAULT_ICON,
                    'fields' => $this->firstFields($type),
                    'newUrl' => route('possessions.new', ['vault' => $this->vault, 'possessionType' => $type]),
                ])
                ->all();
        });
    }

    public function hasLocations(): bool
    {
        return $this->locationsLine() !== '';
    }

    public function locationsLine(): string
    {
        return once(function (): string {
            $locations = Location::query()->where('account_id', $this->user->account_id)->get();

            if ($locations->isEmpty()) {
                return '';
            }

            $topLevelNames = LocaleCollation::sortByCollator($locations->whereNull('parent_id'), 'name')
                ->pluck('name')
                ->values();

            $shown = $topLevelNames->take(self::LOCATION_NAME_LIMIT);
            $hiddenCount = $topLevelNames->count() - $shown->count();

            $allButLast = $shown->slice(0, -1);

            $names = match (true) {
                $hiddenCount > 0 => trans_choice(':names and :count more|:names and :count more', $hiddenCount, ['names' => $shown->implode(__(', '))]),
                $allButLast->isEmpty() => (string) $shown->last(),
                default => __(':names and :last', ['names' => $allButLast->implode(__(', ')), 'last' => $shown->last()]),
            };

            return trans_choice(':names, with :count place in all.|:names, with :count places in all.', $locations->count(), ['names' => $names]);
        });
    }

    public function summary(): string
    {
        $count = $this->possessions()->count();
        $locationCount = $this->locationTree()->ordered()->count();
        $locations = trans_choice(':count location|:count locations', $locationCount);
        $value = $this->valueFormatter()->total($this->possessions());

        return match (true) {
            $locationCount === 0 && $value === '—' => trans_choice(':count possession.|:count possessions.', $count),
            $locationCount === 0 => trans_choice(':count possession, :value declared.|:count possessions, :value declared.', $count, ['value' => $value]),
            $value === '—' => trans_choice(':count possession across :locations.|:count possessions across :locations.', $count, ['locations' => $locations]),
            default => trans_choice(':count possession across :locations, :value declared.|:count possessions across :locations, :value declared.', $count, ['locations' => $locations, 'value' => $value]),
        };
    }

    public function tree(): PossessionLocationTreeBlock
    {
        return once(fn (): PossessionLocationTreeBlock => new PossessionLocationTreeBlock(
            vault: $this->vault,
            tree: $this->locationTree(),
            possessions: $this->possessions(),
            current: $this->location,
        ));
    }

    /** @return array{name: string, address: string, count: int, value: string} */
    public function selection(): array
    {
        return once(function (): array {
            $selected = $this->selected();

            return [
                'name' => $this->location instanceof Location ? $this->location->name : __('All locations'),
                'address' => $this->selectionAddress(),
                'count' => $selected->count(),
                'value' => $this->valueFormatter()->total($selected),
            ];
        });
    }

    /** @return list<array{name: string, count: int, colour: string}> */
    public function parts(): array
    {
        return once(function (): array {
            $selected = $this->selected();
            $palette = VaultColourEnum::cases();

            $places = $this->locationTree()->childrenOf($this->location)
                ->values()
                ->map(fn (Location $place, int $index): array => [
                    'name' => $place->name,
                    'count' => $this->tree()->countIn($place),
                    'colour' => $this->location instanceof Location
                        ? $palette[$index % count($palette)]->value
                        : $this->tree()->colourOf($place),
                ])
                ->filter(fn (array $part): bool => $part['count'] > 0)
                ->values()
                ->all();

            $leftOver = $selected
                ->filter(fn (Possession $possession): bool => $possession->current_location_id === $this->location?->id)
                ->count();

            if ($leftOver > 0) {
                $places[] = [
                    'name' => $this->location instanceof Location ? __('No specific spot') : __('No location'),
                    'count' => $leftOver,
                    'colour' => 'var(--muted-soft)',
                ];
            }

            return count($places) < 2 ? [] : $places;
        });
    }

    public function partsLabel(): string
    {
        return collect($this->parts())
            ->map(fn (array $part): string => "{$part['name']} {$part['count']}")
            ->implode(__(', '));
    }

    /** @return list<array{name: string, url: string, type: string, icon: string, tint: string, place: string, value: string, description: ?string, isLent: bool}> */
    public function featured(): array
    {
        return once(fn (): array => $this->selected()
            ->filter(fn (Possession $possession): bool => $possession->is_featured)
            ->sortBy([['position', 'asc'], ['name', 'asc']])
            ->map(fn (Possession $possession): array => $this->row($possession))
            ->values()
            ->all());
    }

    /** @return list<array{name: string, url: string, type: string, icon: string, tint: string, place: string, value: string, description: ?string, isLent: bool}> */
    public function others(): array
    {
        return once(fn (): array => LocaleCollation::sortByCollator(
            $this->selected()->reject(fn (Possession $possession): bool => $possession->is_featured),
            'name',
        )
            ->map(fn (Possession $possession): array => $this->row($possession))
            ->values()
            ->all());
    }

    public function othersTitle(): string
    {
        if (! $this->location instanceof Location) {
            return __('Everything else');
        }

        return __('Kept at :name', ['name' => $this->location->name]);
    }

    /** @return Collection<int, Possession> */
    private function possessions(): Collection
    {
        return once(fn (): Collection => Possession::query()
            ->where('vault_id', $this->vault->id)
            ->with(['possessionType', 'activeLoan'])
            ->get()
            ->toBase());
    }

    /** @return Collection<int, Possession> */
    private function selected(): Collection
    {
        return once(function (): Collection {
            if (! $this->location instanceof Location) {
                return $this->possessions();
            }

            $ids = [$this->location->id, ...$this->locationTree()->descendantIds($this->location)];

            return $this->possessions()
                ->filter(fn (Possession $possession): bool => in_array($possession->current_location_id, $ids, true))
                ->values();
        });
    }

    private function locationTree(): LocationTree
    {
        return once(fn (): LocationTree => LocationTree::forAccount($this->user->account_id));
    }

    private function valueFormatter(): PossessionValueFormatter
    {
        return once(fn (): PossessionValueFormatter => new PossessionValueFormatter($this->user));
    }

    private function selectionAddress(): string
    {
        $tree = $this->locationTree();

        if (! $this->location instanceof Location) {
            $addressCount = $tree->ordered()->filter(fn (Location $location): bool => $location->address !== null)->count();

            if ($addressCount === 0) {
                return '';
            }

            return trans_choice(':count address|:count addresses', $addressCount);
        }

        if ($this->location->address !== null) {
            return $this->location->address;
        }

        $holder = $tree->addressHolder($this->location);
        $path = $tree->path($this->location);

        if (! $holder instanceof Location) {
            return $path;
        }

        return __(':path, at the address of :holder', ['path' => $path, 'holder' => $holder->name]);
    }

    /** @return array{name: string, url: string, type: string, icon: string, tint: string, place: string, value: string, description: ?string, isLent: bool} */
    private function row(Possession $possession): array
    {
        $type = $possession->possessionType;

        return [
            'name' => $possession->name,
            'url' => route('possessions.show', [$this->vault, $possession]),
            'type' => $type->name,
            'icon' => $type->icon ?? Section::DEFAULT_ICON,
            'tint' => VaultColourEnum::tintFor($type->colour),
            'place' => $this->place($possession),
            'value' => $this->valueFormatter()->format($possession->declared_value, $possession->declared_value_currency),
            'description' => $possession->description,
            'isLent' => $possession->activeLoan?->direction === PossessionLoanDirectionEnum::Lent,
        ];
    }

    private function place(Possession $possession): string
    {
        $tree = $this->locationTree();
        $location = $possession->current_location_id === null ? null : $tree->find($possession->current_location_id);

        if (! $location instanceof Location) {
            return __('No location');
        }

        if (! $this->location instanceof Location) {
            return $tree->path($location);
        }

        if ($location->id === $this->location->id) {
            return __('No specific spot');
        }

        $selectedPath = $tree->path($this->location);

        return mb_substr($tree->path($location), mb_strlen($selectedPath) + mb_strlen(' › '));
    }

    private function firstFields(PossessionType $type): string
    {
        $names = $type->groups
            ->flatMap(fn (PossessionFieldGroup $group) => $group->fields)
            ->concat($type->standaloneFields)
            ->take(2)
            ->map(fn (PossessionField $field): string => $field->name);

        if ($names->isEmpty()) {
            return __('No field yet');
        }

        return $names->implode(__(', '));
    }
}
