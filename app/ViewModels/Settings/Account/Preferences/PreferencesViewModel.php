<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\DateFormatEnum;
use App\Enums\TimeFormatEnum;
use App\Models\User;

/**
 * What the preferences screen shows: the language the interface is in, the
 * clock times are written on, the order dates read in, and the shell around it.
 */
class PreferencesViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * Every language the interface is translated into, ready for the menu that
     * picks one.
     *
     * @return array<int, array{value: string, label: string, hint: string, selected: bool}>
     */
    public function locales(): array
    {
        $current = $this->locale();

        return collect(config('monica.locales'))
            ->map(fn (array $locale, string $code): array => [
                'value' => $code,
                'label' => $locale['label'],
                'hint' => $locale['region'],
                'selected' => $code === $current,
            ])
            ->values()
            ->all();
    }

    /**
     * The name of the language the interface is in, shown on the button that
     * opens the menu.
     */
    public function localeLabel(): string
    {
        return config('monica.locales')[$this->locale()]['label'];
    }

    /**
     * Both ways of writing the time, each next to the same afternoon hour so
     * the difference between them is on the screen rather than in the name.
     *
     * @return array<int, array{value: string, label: string, hint: string, selected: bool}>
     */
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

    /**
     * The time it is now, written the way the account asked for, so somebody
     * can see what they picked before they leave the screen.
     */
    public function timePreview(): string
    {
        return $this->timeFormat()->format(now());
    }

    /**
     * Every order a date can read in, each next to the same day written that
     * way. The first entry is the way out of the choice: it hands the decision
     * back to the language, which is where somebody starts.
     *
     * @return array<int, array{value: string, label: string, hint: string, selected: bool}>
     */
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

    /**
     * What the button that opens the menu says. It shows the day rather than
     * the name of the order, since the point of the choice is what it looks
     * like and the names all read alike.
     */
    public function dateFormatLabel(): string
    {
        return $this->dateFormat()?->example() ?? $this->languageDateExample();
    }

    /**
     * Today written the way the account asked for, so somebody can see what
     * they picked before they leave the screen.
     */
    public function datePreview(): string
    {
        $format = $this->dateFormat();

        return $format === null
            ? DateFormatEnum::write(now(), $this->languageDateFormat())
            : $format->format(now());
    }

    /**
     * Who is signed in, so the sidebar can show their name and their photo.
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * The language the screen was drawn in, which is what the middleware
     * settled on rather than only what the account asks for: somebody who
     * switched language from a guest screen sees that choice reflected here.
     *
     * Each row saves on its own, so the row that does not own this value still
     * has to send it along, which is why it is readable from the screen.
     */
    public function locale(): string
    {
        $locale = app()->getLocale();

        return array_key_exists($locale, config('monica.locales'))
            ? $locale
            : config('app.locale');
    }

    public function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }

    /**
     * The order the account reads dates in, null while it follows the language.
     */
    public function dateFormat(): ?DateFormatEnum
    {
        return $this->user->date_format;
    }

    /**
     * The order the language being read writes dates in, which is what somebody
     * who has never touched this setting gets.
     */
    private function languageDateFormat(): string
    {
        return config('monica.locales')[$this->locale()]['date_format'];
    }

    private function languageDateExample(): string
    {
        return DateFormatEnum::write(now()->setDay(21), $this->languageDateFormat());
    }
}
