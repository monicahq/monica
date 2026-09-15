<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class LocaleResolution
{
    public const string SESSION_KEY = 'locale';

    public const string COOKIE_NAME = 'locale';

    public const int COOKIE_MINUTES = 60 * 24 * 365;

    public const string USER_ATTRIBUTE = 'locale';

    public static function forWeb(Request $request): string
    {
        return self::fromSession()
            ?? self::fromUser()
            ?? self::fromCookie()
            ?? self::fromAnnouncedLanguage($request)
            ?? self::fromApplication();
    }

    public static function forApi(Request $request): string
    {
        return self::fromAnnouncedLanguage($request)
            ?? self::fromUser(guard: 'sanctum')
            ?? self::fromApplication();
    }

    public static function remember(string $locale): void
    {
        Session::put(self::SESSION_KEY, $locale);
        Cookie::queue(self::COOKIE_NAME, $locale, self::COOKIE_MINUTES);
        App::setLocale($locale);
    }

    public static function fromSession(): ?string
    {
        return self::shipped(Session::get(self::SESSION_KEY));
    }

    public static function fromUser(?string $guard = null): ?string
    {
        return self::shipped(Auth::guard($guard)->user()?->getAttributeValue(self::USER_ATTRIBUTE));
    }

    public static function fromCookie(): ?string
    {
        return self::shipped(Cookie::get(self::COOKIE_NAME));
    }

    public static function fromAnnouncedLanguage(Request $request): ?string
    {
        foreach ($request->getLanguages() as $announced) {
            $match = self::shipped($announced) ?? self::shippedForLanguageOf($announced);

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    public static function fromApplication(): string
    {
        return (string) config('app.locale');
    }

    private static function shipped(mixed $locale): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        return array_key_exists($locale, self::locales()) ? $locale : null;
    }

    private static function shippedForLanguageOf(string $announced): ?string
    {
        $language = self::languageOf($announced);

        $candidates = array_filter(
            self::locales(),
            fn (array $locale, string $key): bool => self::languageOf($key) === $language,
            ARRAY_FILTER_USE_BOTH,
        );

        if (count($candidates) === 1) {
            return array_key_first($candidates);
        }

        foreach ($candidates as $key => $locale) {
            if ($locale['answers_bare_language'] ?? false) {
                return $key;
            }
        }

        return null;
    }

    private static function languageOf(string $locale): string
    {
        return mb_strtolower(explode('_', $locale)[0]);
    }

    /** @return array<string, array<string, mixed>> */
    private static function locales(): array
    {
        return AvailableLocales::all();
    }
}
