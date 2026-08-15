<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;

/**
 * The password a browser already gave for a link.
 *
 * Two screens need it and have to agree about it: the form that accepts it, and
 * the page that is opened afterwards and every time a tab is clicked. Without
 * this, walking between two tabs of a shared page would ask for the password
 * again, and both alternatives were worse than keeping it: re-posting the form on
 * every refresh, or a second way into the record that skips the password and would
 * one day be reached by something that should not have skipped it.
 *
 * The key is the hash of the token rather than the token, so the session holds no
 * address that opens anything. The password itself is held server side, which is
 * where the session lives, and only for as long as the visitor's session does.
 */
class ShareLinkPassword
{
    public static function remembered(Request $request, string $token): ?string
    {
        $password = $request->session()->get(self::key($token));

        return is_string($password) ? $password : null;
    }

    public static function remember(Request $request, string $token, string $password): void
    {
        $request->session()->put(self::key($token), $password);
    }

    /**
     * Called when a remembered password stops working, which happens when the
     * owner changes it. Keeping it would write a failed visit on every click.
     */
    public static function forget(Request $request, string $token): void
    {
        $request->session()->forget(self::key($token));
    }

    private static function key(string $token): string
    {
        return 'share-link.'.hash('sha256', $token);
    }
}
