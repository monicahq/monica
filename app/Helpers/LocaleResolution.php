<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

/**
 * Which of the nine languages a request is answered in.
 *
 * Five places may hold an answer, and they are read in an order that differs by
 * surface. The rule that makes the whole thing safe is that every one of them
 * returns a key of `config('monica.locales')` or nothing at all: a value the
 * product does not ship never leaves this class, so a forged cookie or header
 * cannot put an untranslated screen in front of anybody.
 *
 * The shape is borrowed from asbiin/laravel-localizer. That package expresses
 * the sources as interchangeable objects a host arranges, which is what a
 * package needs; this product knows both of its orders and wants no third, so
 * the same idea is two sequences instead.
 */
class LocaleResolution
{
    /**
     * The key a choice has always been kept under. Moving it would drop
     * everybody mid visit back into English on the deploy.
     */
    public const string SESSION_KEY = 'locale';

    /** The cookie a choice outlives its session in. */
    public const string COOKIE_NAME = 'locale';

    /** A year, which is how long a language somebody picked stays picked. */
    public const int COOKIE_MINUTES = 60 * 24 * 365;

    /** The column on the user that holds the preference following them between devices. */
    public const string USER_ATTRIBUTE = 'locale';

    /**
     * What a screen is shown in. A choice made in this session, then the account,
     * then a choice made on this device before, then what the browser announces,
     * then the fallback.
     */
    public static function forWeb(Request $request): string
    {
        return self::fromSession()
            ?? self::fromUser()
            ?? self::fromCookie()
            ?? self::fromAnnouncedLanguage($request)
            ?? self::fromApplication();
    }

    /**
     * What an API response is written in. The header comes first here and last on
     * a screen, because a caller naming a language on a request has asked for it,
     * where a browser announcing one has only ever been configured. Neither the
     * session nor the cookie takes part: an API request carries neither, and
     * answering one leaves nothing behind.
     */
    public static function forApi(Request $request): string
    {
        return self::fromAnnouncedLanguage($request)
            ?? self::fromUser(guard: 'sanctum')
            ?? self::fromApplication();
    }

    /**
     * Record a language somebody asked for, everywhere it belongs, in one call so
     * the three can never disagree. Only ever reached from the two screens where
     * somebody picks one, never from a middleware: a language nobody chose is
     * resolved afresh each time rather than kept on their device.
     */
    public static function remember(string $locale): void
    {
        Session::put(self::SESSION_KEY, $locale);
        Cookie::queue(self::COOKIE_NAME, $locale, self::COOKIE_MINUTES);
        App::setLocale($locale);
    }

    /** What was picked in this session, and the last thing picked wins over the account. */
    public static function fromSession(): ?string
    {
        return self::shipped(Session::get(self::SESSION_KEY));
    }

    /**
     * What the signed in account is set to, which follows somebody between devices.
     *
     * The guard has to be named on the api side. That surface authenticates with
     * a token rather than a session, and this runs on the whole group, which is
     * before the route says a token is required, so the default guard is still
     * the session one and still empty. Naming it reads the token directly.
     */
    public static function fromUser(?string $guard = null): ?string
    {
        return self::shipped(Auth::guard($guard)->user()?->getAttributeValue(self::USER_ATTRIBUTE));
    }

    /** What was picked on this device before, which outlives the session it was picked in. */
    public static function fromCookie(): ?string
    {
        return self::shipped(Cookie::get(self::COOKIE_NAME));
    }

    /**
     * The first language the browser or the caller announces that this product has
     * something to say in.
     *
     * The framework has already split the header, sorted it by the weights it
     * carries and written each entry with an underscore, so `fr-CH` arrives as
     * `fr_CH`. What is left is that a well formed language is not necessarily one
     * of the nine.
     */
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

    /** The language of the installation, which is always set, so resolution cannot fail. */
    public static function fromApplication(): string
    {
        return (string) config('app.locale');
    }

    /** The value back if this product ships it, and nothing at all otherwise. */
    private static function shipped(mixed $locale): ?string
    {
        if (! is_string($locale)) {
            return null;
        }

        return array_key_exists($locale, self::locales()) ? $locale : null;
    }

    /**
     * What to answer somebody who named a language this product ships and a region
     * it does not, which is how Swiss French reaches French, and somebody who named
     * no region at all.
     *
     * Where a language has one locale that locale answers. Where it has two, as
     * Portuguese does, the one that says so answers, because picking either
     * silently is a coin toss the reader notices.
     */
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

    /** Everything before the first underscore, which is the language and not the region. */
    private static function languageOf(string $locale): string
    {
        return mb_strtolower(explode('_', $locale)[0]);
    }

    /** @return array<string, array<string, mixed>> */
    private static function locales(): array
    {
        return config('monica.locales');
    }
}
