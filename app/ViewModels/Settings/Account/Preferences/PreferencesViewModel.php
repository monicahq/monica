<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Models\Employee;
use App\Models\User;

/**
 * What the preferences screen shows: the language the interface is in, the
 * clock times are written on, and the shell around it.
 */
class PreferencesViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
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

        return collect(config('officelife.locales'))
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
        return config('officelife.locales')[$this->locale()]['label'];
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
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the sidebar can show the photo when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function companyName(): string
    {
        return $this->user->company->name;
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

        return array_key_exists($locale, config('officelife.locales'))
            ? $locale
            : config('app.locale');
    }

    public function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }
}
