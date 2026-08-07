<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

/**
 * What every screen a visitor sees before signing in has in common: the line
 * from The Office on the side, and the language picker at the bottom.
 */
abstract class GuestViewModel
{
    /**
     * One line from The Office, for the panel on the side of the screen.
     *
     * @return array{text: string, author: string, source: string}
     */
    public function quote(): array
    {
        $quotes = config('quotes');

        return $quotes[array_rand($quotes)];
    }

    /**
     * The languages the interface is available in, for the picker.
     *
     * @return array<int, array{code: string, label: string, region: string, flag: string}>
     */
    public function locales(): array
    {
        return collect(config('officelife.locales'))
            ->map(fn (array $locale, string $code): array => [...$locale, 'code' => $code])
            ->values()
            ->all();
    }

    public function currentLocale(): string
    {
        return app()->getLocale();
    }
}
