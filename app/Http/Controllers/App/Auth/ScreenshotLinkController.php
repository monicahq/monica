<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Sign somebody in from a link, so a screenshot tool that can only open a URL can reach a screen
 * that lives behind the sign in.
 *
 * This is a development affordance, and what makes it safe is the signature: the link is signed
 * with the application key and expires, so minting one already takes everything an attacker would
 * need anyway. Everything else is there so that a mistake somewhere else does not become a way in.
 *
 * The route is registered outside production only, so a deployed instance does not carry it. The
 * setting is off until somebody writes it. The host has to be a development one, which is asked
 * because `.env.example` ships `APP_ENV=local` and an instance deployed from it would otherwise
 * rest on that one setting. And the screen the link lands on has to be a path of this application.
 *
 * It signs in without asking for a second factor and without asking whether the account is active,
 * which is the whole point of it and another reason it may never be reachable in production.
 *
 * Nothing is written to the account's journal, deliberately. A screenshot of the logs screen would
 * otherwise show the sign in that was performed to take it.
 */
class ScreenshotLinkController extends Controller
{
    public function show(Request $request, string $email): RedirectResponse
    {
        abort_unless(self::isAvailable(), 404);
        abort_unless(self::isDevelopmentHost($request->getHost()), 404);

        $user = User::query()->where('email', $email)->first();

        abort_if($user === null, 404);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return redirect(self::path($request->query('to')));
    }

    /**
     * Whether the link may be used at all. The environment is asked again here rather than trusted
     * from the route file, so a route registered by mistake still leads nowhere.
     */
    public static function isAvailable(): bool
    {
        return app()->environment('local', 'testing')
            && (bool) config('monica.screenshot_link.enabled');
    }

    /**
     * Whether a host is one somebody develops on.
     *
     * @param  string  $host  the host of the request, or of the address the instance answers on
     */
    public static function isDevelopmentHost(string $host): bool
    {
        /** @var array<int, string> $allowed */
        $allowed = config('monica.screenshot_link.allowed_hosts', []);

        return Str::is($allowed, mb_strtolower($host));
    }

    /**
     * Where the link lands, which is always a path of this application.
     *
     * A signature already makes this unforgeable, so the check is here for the person who is handed
     * a link and follows it: a value naming another host, or a scheme, is dropped rather than
     * followed. Anything carrying a control character is dropped outright, because a browser strips
     * those and what it is left with is not what was read here.
     */
    private static function path(mixed $wanted): string
    {
        if (! is_string($wanted) || $wanted === '') {
            return '/';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $wanted) === 1) {
            return '/';
        }

        if (! str_starts_with($wanted, '/')) {
            return '/';
        }

        if (str_starts_with($wanted, '//') || str_starts_with($wanted, '/\\')) {
            return '/';
        }

        if (str_contains($wanted, '://')) {
            return '/';
        }

        return $wanted;
    }
}
