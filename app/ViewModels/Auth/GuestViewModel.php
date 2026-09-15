<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

use App\Helpers\AvailableLocales;

abstract class GuestViewModel
{
    /** @return array{text: string, author: string, source: string} */
    public function quote(): array
    {
        $quotes = config('quotes');

        return $quotes[array_rand($quotes)];
    }

    /** @return array<int, array{code: string, label: string, region: string, flag: string}> */
    public function locales(): array
    {
        return collect(AvailableLocales::all())
            ->map(fn (array $locale, string $code): array => [
                'code' => $code,
                'label' => $locale['label'],
                'region' => $locale['region'],
                'flag' => $locale['flag'],
            ])
            ->sortByCollator('label')
            ->all();
    }

    public function currentLocale(): string
    {
        return app()->getLocale();
    }
}
