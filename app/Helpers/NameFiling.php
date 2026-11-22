<?php

declare(strict_types=1);

namespace App\Helpers;

class NameFiling
{
    public static function sortName(string $locale, ?string $family, ?string $given, ?string $additional, string $fallback = ''): string
    {
        $rule = config("localization.locales.{$locale}.name_filing", []);

        $family = LocaleCollation::lower(mb_trim((string) $family), $locale);
        $skipped = [];

        while (($particle = self::openingParticle($family, $rule['skipped_particles'] ?? [])) !== null) {
            $skipped[] = $particle;
            $family = mb_ltrim(mb_substr($family, mb_strlen($particle)));
        }

        $parts = ($rule['files_under'] ?? 'family') === 'given'
            ? [$given, $additional, $family]
            : [$family, $given, $additional];

        $text = collect([...$parts, ...$skipped])
            ->map(fn (?string $part): string => mb_trim((string) $part))
            ->filter(fn (string $part): bool => $part !== '')
            ->implode(' ');

        if ($text === '') {
            $text = $fallback;
        }

        return mb_substr(LocaleCollation::lower(mb_trim($text), $locale), 0, 511);
    }

    /** @return list<string> */
    public static function phoneticScripts(string $locale): array
    {
        return config("localization.locales.{$locale}.name_filing.phonetic_scripts", []);
    }

    /** @param  list<string>  $particles */
    private static function openingParticle(string $family, array $particles): ?string
    {
        usort($particles, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        foreach ($particles as $particle) {
            if (! str_starts_with($family, $particle)) {
                continue;
            }

            $rest = mb_substr($family, mb_strlen($particle));

            if (mb_trim($rest) === '') {
                continue;
            }

            if (str_starts_with($rest, ' ')) {
                return $particle;
            }

            if (in_array(mb_substr($particle, -1), ["'", "\u{2019}"], true)) {
                return $particle;
            }
        }

        return null;
    }
}
