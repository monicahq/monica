<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Helpers\TimezoneCatalogue;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * What the time zone section shows: the zone somebody is in, the time it is
 * there, where that lands on the map, and the list the search field filters.
 *
 * It sits beside the preferences view model rather than inside it because the
 * section carries a search index of four hundred entries and a marker position,
 * none of which the four format rows have any business holding.
 */
class TimezonePanel
{
    public function __construct(
        private readonly User $user,
        private readonly bool $isGuess = false,
    ) {}

    /**
     * The stored identifier, which is what the endpoint takes back and what the
     * screen shows as the answer.
     */
    public function timezone(): string
    {
        return $this->user->timezone;
    }

    /**
     * How far the zone runs from UTC, written the way a reader expects to see
     * it rather than in minutes. Read now rather than stored, because a zone
     * that keeps summer time is not the same distance from UTC all year.
     */
    public function offset(): string
    {
        return CarbonImmutable::now($this->timezone())->format('P');
    }

    /**
     * The time it is where somebody is, on the clock they chose. It is the one
     * thing on the screen that proves the zone took effect.
     */
    public function clock(): string
    {
        return $this->timeFormat()->format(CarbonImmutable::now($this->timezone()));
    }

    /**
     * The city and country the zone names, so somebody reading the screen
     * recognises the answer without decoding an identifier. Empty for the
     * handful of zones belonging to no country, `UTC` among them, which is
     * where an account that has never touched this starts.
     */
    public function place(): string
    {
        $zone = $this->zone();

        if ($zone === null || $zone['country'] === '') {
            return '';
        }

        return "{$zone['city']}, {$zone['country']}";
    }

    /**
     * The sentence under the map. It names the place when there is one to name,
     * and falls back to the identifier when there is not.
     */
    public function sentence(): string
    {
        $place = $this->place();

        if ($place === '') {
            return __('Set to :timezone. Dates and reminders are written in it.', ['timezone' => $this->timezone()]);
        }

        return __('Set to :place. Dates and reminders are written in :timezone.', [
            'place' => $place,
            'timezone' => $this->timezone(),
        ]);
    }

    /**
     * Whether the zone came from a click rather than from a name. A click gives
     * the nearest zone to wherever it landed, which is a guess, and the screen
     * says so rather than letting somebody assume it was read off their pointer.
     */
    public function isGuess(): bool
    {
        return $this->isGuess;
    }

    /**
     * Where the marker sits, as a percentage of the map's width and height, so
     * it is placed by the browser without any arithmetic in the template.
     *
     * @return array{x: float, y: float}
     */
    public function marker(): array
    {
        $zone = $this->zone();

        if ($zone === null) {
            return TimezoneCatalogue::position(0.0, 0.0);
        }

        return TimezoneCatalogue::position($zone['latitude'], $zone['longitude']);
    }

    /**
     * The hour of the world the zone falls in, as a left edge and a width in
     * percentages of the map.
     *
     * Snapped to the fifteen degree grid rather than centred on the city, so it
     * lands between two of the map's own meridians. An hour is a slice of the
     * globe, not a margin either side of wherever a place happens to sit, and a
     * band drawn the second way sits askew of every line behind it.
     *
     * It is clipped at the edges of the map: the slice around the antimeridian
     * is half off each end, and the half that shows is the half that fits.
     *
     * @return array{left: float, width: float}
     */
    public function band(): array
    {
        $centre = round($this->longitude() / 15) * 15;
        $left = max(-180.0, $centre - 7.5);
        $right = min(180.0, $centre + 7.5);

        return [
            'left' => round(($left + 180.0) / 360.0 * 100, 4),
            'width' => round(($right - $left) / 360.0 * 100, 4),
        ];
    }

    private function longitude(): float
    {
        return $this->zone()['longitude'] ?? 0.0;
    }

    /**
     * Every zone, ready for the list the search filters.
     *
     * Each one carries the time it is there, because that is what tells two
     * zones an hour apart from one another, and the line the search matches on.
     * That line rides with the row rather than arriving separately, so there is
     * one copy of it and no index to keep in step with the list it describes.
     *
     * @return array<int, array{timezone: string, name: string, offset: string, clock: string, search: string, selected: bool}>
     */
    public function zones(): array
    {
        $current = $this->timezone();
        $format = $this->timeFormat();
        $now = CarbonImmutable::now();
        $index = TimezoneCatalogue::searchIndex();

        return array_map(function (array $zone) use ($current, $format, $now, $index): array {
            $there = $now->setTimezone($zone['timezone']);

            return [
                'timezone' => $zone['timezone'],
                'name' => $zone['country'] === ''
                    ? $zone['city']
                    : "{$zone['city']}, {$zone['country']}",
                'offset' => $there->format('P'),
                'clock' => $format->format($there),
                'search' => $index[$zone['timezone']],
                'selected' => $zone['timezone'] === $current,
            ];
        }, TimezoneCatalogue::all());
    }

    /**
     * @return array{timezone: string, city: string, country: string, latitude: float, longitude: float}|null
     */
    private function zone(): ?array
    {
        return TimezoneCatalogue::find($this->timezone());
    }

    private function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }
}
