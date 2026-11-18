<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\DateFormatEnum;
use App\Enums\NumberFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Helpers\AvailableLocales;
use App\Models\User;
use Carbon\CarbonImmutable;

class PreferencesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function locales(): array
    {
        $current = $this->locale();

        return collect(AvailableLocales::all())
            ->map(fn (array $locale, string $code): array => [
                'value' => $code,
                'label' => $locale['label'],
                'hint' => $locale['region'],
                'selected' => $code === $current,
            ])
            ->sortByCollator('label')
            ->all();
    }

    public function localeLabel(): string
    {
        return (string) AvailableLocales::details($this->locale())['label'];
    }

    public function usesReadableAddresses(): bool
    {
        return $this->user->uses_readable_addresses;
    }

    public function showsVaultList(): bool
    {
        return $this->user->shows_vault_list;
    }

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function addressStyles(): array
    {
        return [
            [
                'value' => '1',
                'label' => __('Readable names'),
                'hint' => '/vaults/famille/contacts/jim-halpert',
                'selected' => $this->usesReadableAddresses(),
            ],
            [
                'value' => '0',
                'label' => __('Identifiers'),
                'hint' => '/vaults/9f3c8b1e/contacts/1a7e5c90',
                'selected' => ! $this->usesReadableAddresses(),
            ],
        ];
    }

    public function addressStyleLabel(): string
    {
        return $this->usesReadableAddresses() ? __('Readable names') : __('Identifiers');
    }

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function timeFormats(): array
    {
        return collect(TimeFormatEnum::cases())
            ->map(fn (TimeFormatEnum $format): array => [
                'value' => $format->value,
                'label' => __($format->label()),
                'hint' => $format->example(),
                'selected' => $format === $this->timeFormat(),
            ])
            ->all();
    }

    public function timeFormatLabel(): string
    {
        return __($this->timeFormat()->label());
    }

    public function timePreview(): string
    {
        return $this->timeFormat()->format($this->now());
    }

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function dateFormats(): array
    {
        $current = $this->dateFormat();

        $formats = [[
            'value' => '',
            'label' => __('Follow the language'),
            'hint' => $this->languageDateExample(),
            'selected' => $current === null,
        ]];

        foreach (DateFormatEnum::cases() as $format) {
            $formats[] = [
                'value' => $format->value,
                'label' => __($format->label()),
                'hint' => $format->example(),
                'selected' => $format === $current,
            ];
        }

        return $formats;
    }

    public function dateFormatLabel(): string
    {
        return $this->dateFormat()?->example() ?? $this->languageDateExample();
    }

    public function datePreview(): string
    {
        $format = $this->dateFormat();

        return $format === null
            ? DateFormatEnum::write($this->now(), $this->languageDateFormat())
            : $format->format($this->now());
    }

    public function timezone(bool $isGuess = false): TimezonePanel
    {
        return new TimezonePanel(user: $this->user, isGuess: $isGuess);
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->user->timezone);
    }

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function numberFormats(): array
    {
        $current = $this->numberFormat();

        $formats = [[
            'value' => '',
            'label' => __('Follow the language'),
            'hint' => $this->languageNumberFormat()->example(),
            'selected' => $current === null,
        ]];

        foreach (NumberFormatEnum::cases() as $format) {
            $formats[] = [
                'value' => $format->value,
                'label' => __($format->label()),
                'hint' => $format->example(),
                'selected' => $format === $current,
            ];
        }

        return $formats;
    }

    public function numberFormatLabel(): string
    {
        return ($this->numberFormat() ?? $this->languageNumberFormat())->example();
    }

    public function user(): User
    {
        return $this->user;
    }

    public function locale(): string
    {
        $locale = app()->getLocale();

        return AvailableLocales::has($locale)
            ? $locale
            : (string) config('app.locale');
    }

    public function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }

    public function dateFormat(): ?DateFormatEnum
    {
        return $this->user->date_format;
    }

    private function languageDateFormat(): string
    {
        return (string) AvailableLocales::details($this->locale())['date_format'];
    }

    private function languageDateExample(): string
    {
        return DateFormatEnum::write(now()->setDay(21), $this->languageDateFormat());
    }

    public function numberFormat(): ?NumberFormatEnum
    {
        return $this->user->number_format;
    }

    private function languageNumberFormat(): NumberFormatEnum
    {
        return NumberFormatEnum::from((string) AvailableLocales::details($this->locale())['number_format']);
    }
}
