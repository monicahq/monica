<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\DateFormatEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\LocationTree;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The Locations section of a possession: where it is kept now, and every stay since it was recorded,
 * newest first. Lengths, the gaps between stays and the counts are worked out from the stays each
 * time.
 */
class PossessionLocationsViewModel
{
    private readonly LocationTree $tree;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly ?PossessionLocationStay $editing = null,
    ) {
        $this->possession->load('locationStays');
        $this->tree = LocationTree::forAccount($this->possession->account_id);
    }

    public function page(): PossessionViewModel
    {
        return once(fn (): PossessionViewModel => new PossessionViewModel(user: $this->user, possession: $this->possession));
    }

    public function locationsUrl(): string
    {
        return route('settings.locations.index');
    }

    /** @return array{head: string, leaf: string, address: ?string, since: string, held: string, reason: ?string, editUrl: string}|null */
    public function current(): ?array
    {
        $open = $this->open();

        if (! $open instanceof PossessionLocationStay) {
            return null;
        }

        $location = $this->locationOf($open);

        return [
            'head' => $this->head($location),
            'leaf' => $location->name,
            'address' => $this->tree->addressHolder($location)?->address,
            'since' => $open->arrived_on === null ? __('Date unknown') : $this->writeDate($open->arrived_on),
            'held' => $this->length($open->arrived_on, null),
            'reason' => $open->reason,
            'editUrl' => route('possessions.locationStays.edit', [$this->possession->vault, $this->possession, $open]),
        ];
    }

    public function countLine(): string
    {
        $stays = $this->possession->locationStays;

        if ($stays->isEmpty()) {
            return '';
        }

        return trans_choice(':count stay in :locations|:count stays in :locations', $stays->count(), [
            'locations' => trans_choice(':count location|:count locations', $stays->pluck('location_id')->unique()->count()),
        ]);
    }

    /**
     * Every stay, newest first, with a line between two stays where nothing was recorded. The stay
     * being corrected is left in, since the form opens above the list.
     *
     * @return list<array{kind: string, id?: string, head?: string, leaf?: string, isCurrent?: bool, period?: string, length?: string, reason?: ?string, note?: ?string, editUrl?: string, destroyUrl?: string, deleteTitle?: string, deleteWarning?: string, text?: string}>
     */
    public function rows(): array
    {
        $stays = $this->possession->locationStays->values();
        $rows = [];

        for ($index = $stays->count() - 1; $index >= 0; $index--) {
            $stay = $stays[$index];
            $older = $index > 0 ? $stays[$index - 1] : null;
            $rows[] = $this->row($stay, $older);

            if ($older instanceof PossessionLocationStay && $older->left_on !== null && $stay->arrived_on !== null && $older->left_on->lessThan($stay->arrived_on)) {
                $rows[] = [
                    'kind' => 'gap',
                    'text' => __('No location recorded from :from to :to, :length.', [
                        'from' => $this->writeDate($older->left_on),
                        'to' => $this->writeDate($stay->arrived_on),
                        'length' => $this->length($older->left_on, $stay->arrived_on),
                    ]),
                ];
            }
        }

        return $rows;
    }

    /**
     * Every location of the account, written as its full path so that two locations with the same
     * name are told apart.
     *
     * @return list<array{value: string, label: string}>
     */
    public function locationOptions(): array
    {
        return $this->tree->ordered()
            ->map(fn (Location $location): array => [
                'value' => $location->uuid,
                'label' => $this->tree->path($location),
            ])
            ->values()
            ->all();
    }

    /**
     * What the form starts with: the stay being corrected, else a move to be chosen, dated today.
     *
     * @return array{isEditing: bool, isOpenStay: bool, action: string, method: string, cancelUrl: ?string, title: string, subtitle: string, values: array<string, string>}
     */
    public function form(): array
    {
        $editing = $this->editing;
        $open = $this->open();

        if (! $editing instanceof PossessionLocationStay) {
            return [
                'isEditing' => false,
                'isOpenStay' => false,
                'action' => route('possessions.locationStays.create', [$this->possession->vault, $this->possession]),
                'method' => 'post',
                'cancelUrl' => null,
                'title' => __('Move the possession'),
                'subtitle' => $open instanceof PossessionLocationStay
                    ? __('From :name', ['name' => $this->locationOf($open)->name])
                    : __('It has no current location'),
                'values' => [
                    'moved_on' => $this->today()->toDateString(),
                ],
            ];
        }

        return [
            'isEditing' => true,
            'isOpenStay' => $editing->isOpen(),
            'action' => route('possessions.locationStays.update', [$this->possession->vault, $this->possession, $editing]),
            'method' => 'put',
            'cancelUrl' => route('possessions.locationStays.index', [$this->possession->vault, $this->possession]),
            'title' => __('Correct this stay'),
            'subtitle' => $editing->isOpen()
                ? __('The current stay. Move the possession to close it.')
                : __('A past stay'),
            'values' => array_map(fn (?string $value): string => (string) $value, [
                'location_id' => $this->locationOf($editing)->uuid,
                'arrived_on' => $editing->arrived_on?->toDateString(),
                'left_on' => $editing->left_on?->toDateString(),
                'reason' => $editing->reason,
                'note' => $editing->note,
            ]),
        ];
    }

    /**
     * The wordings the move form shows as its fields change. `:new` is filled in by the page with the
     * location chosen.
     *
     * @return array{currentLocation: ?string, empty: string, closes: string, opens: string}
     */
    public function preview(): array
    {
        $open = $this->open();
        $location = $open instanceof PossessionLocationStay ? $this->locationOf($open) : null;

        return [
            'currentLocation' => $location?->uuid,
            'empty' => __('Choose a location and a day to see what this move records.'),
            'closes' => $location instanceof Location
                ? __('The stay in :current closes that day. A new stay in :new opens the same day.', ['current' => $location->name])
                : '',
            'opens' => __('A new stay in :new opens that day.'),
        ];
    }

    /** @return array<string, string> */
    public function locationNames(): array
    {
        return $this->tree->ordered()
            ->mapWithKeys(fn (Location $location): array => [$location->uuid => $location->name])
            ->all();
    }

    /** @return array{kind: string, id: string, head: string, leaf: string, isCurrent: bool, period: string, length: string, reason: ?string, note: ?string, editUrl: string, destroyUrl: string, deleteTitle: string, deleteWarning: string} */
    private function row(PossessionLocationStay $stay, ?PossessionLocationStay $older): array
    {
        $location = $this->locationOf($stay);

        return [
            'kind' => 'stay',
            'id' => $stay->uuid,
            'head' => $this->head($location),
            'leaf' => $location->name,
            'isCurrent' => $stay->isOpen(),
            'period' => __(':from to :to', [
                'from' => $stay->arrived_on === null ? __('Date unknown') : $this->writeDate($stay->arrived_on),
                'to' => $stay->left_on === null ? __('now') : $this->writeDate($stay->left_on),
            ]),
            'length' => $this->length($stay->arrived_on, $stay->left_on),
            'reason' => $stay->reason,
            'note' => $stay->note,
            'editUrl' => route('possessions.locationStays.edit', [$this->possession->vault, $this->possession, $stay]),
            'destroyUrl' => route('possessions.locationStays.destroy', [$this->possession->vault, $this->possession, $stay]),
            'deleteTitle' => __('Delete the stay in :name?', ['name' => $location->name]),
            'deleteWarning' => $this->deleteWarning($stay, $older, $location),
        ];
    }

    private function deleteWarning(PossessionLocationStay $stay, ?PossessionLocationStay $older, Location $location): string
    {
        if (! $stay->isOpen()) {
            return __('This period will show as unrecorded, and this cannot be undone. :name itself stays in your locations.', ['name' => $location->name]);
        }

        if ($older instanceof PossessionLocationStay && $older->left_on !== null) {
            return __('The possession will have no current location until it is moved, and this cannot be undone. The stay in :name stays closed on :date.', [
                'name' => $this->locationOf($older)->name,
                'date' => $this->writeDate($older->left_on),
            ]);
        }

        return __('The possession will have no current location until it is moved, and this cannot be undone.');
    }

    /**
     * How long a stay lasted, or has lasted so far: in days under a month, then in years and months.
     */
    private function length(?CarbonImmutable $from, ?CarbonImmutable $to): string
    {
        if (! $from instanceof CarbonImmutable) {
            return __('length unknown');
        }

        $end = $to ?? $this->today();
        $days = (int) $from->diffInDays($end);

        if ($days < 1) {
            return __('less than a day');
        }

        if ($days < 31) {
            return trans_choice(':count day|:count days', $days);
        }

        $months = (int) $from->diffInMonths($end);
        $years = intdiv($months, 12);
        $months %= 12;

        if ($years === 0) {
            return trans_choice(':count month|:count months', $months);
        }

        if ($months === 0) {
            return trans_choice(':count year|:count years', $years);
        }

        return __(':years and :months', [
            'years' => trans_choice(':count year|:count years', $years),
            'months' => trans_choice(':count month|:count months', $months),
        ]);
    }

    private function open(): ?PossessionLocationStay
    {
        return $this->possession->locationStays->first(fn (PossessionLocationStay $stay): bool => $stay->isOpen());
    }

    private function locationOf(PossessionLocationStay $stay): Location
    {
        return $this->tree->find($stay->location_id) ?? $stay->location;
    }

    private function head(Location $location): string
    {
        $parent = $this->tree->parentOf($location);

        return $parent instanceof Location ? "{$this->tree->path($parent)} ›" : '';
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse(CarbonImmutable::now($this->user->timezone)->toDateString());
    }

    private function writeDate(CarbonInterface $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }
}
