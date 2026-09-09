<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Helpers\TimezoneCatalogue;
use App\Models\User;
use Carbon\CarbonImmutable;

class TimezonePanel
{
    public function __construct(
        private readonly User $user,
        private readonly bool $isGuess = false,
    ) {}

    public function timezone(): string
    {
        return $this->user->timezone;
    }

    public function offset(): string
    {
        return CarbonImmutable::now($this->timezone())->format('P');
    }

    public function clock(): string
    {
        return $this->timeFormat()->format(CarbonImmutable::now($this->timezone()));
    }

    public function place(): string
    {
        $zone = $this->zone();

        if ($zone === null || $zone['country'] === '') {
            return '';
        }

        return "{$zone['city']}, {$zone['country']}";
    }

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

    public function isGuess(): bool
    {
        return $this->isGuess;
    }

    /** @return array{x: float, y: float} */
    public function marker(): array
    {
        $zone = $this->zone();

        if ($zone === null) {
            return TimezoneCatalogue::position(0.0, 0.0);
        }

        return TimezoneCatalogue::position($zone['latitude'], $zone['longitude']);
    }

    /**
     * The latitudes the map is cut off at, which the picker needs to read a
     * click back into a place. They are the same two the marker is drawn
     * with, so a click and the dot it produces agree.
     *
     * @return array{north: float, south: float}
     */
    public function projection(): array
    {
        return [
            'north' => TimezoneCatalogue::MAP_NORTH,
            'south' => TimezoneCatalogue::MAP_SOUTH,
        ];
    }

    /** @return array{left: float, width: float} */
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

    /** @return array{timezone: string, city: string, country: string, latitude: float, longitude: float}|null */
    private function zone(): ?array
    {
        return TimezoneCatalogue::find($this->timezone());
    }

    private function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }
}
