<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;

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

    public static function forget(Request $request, string $token): void
    {
        $request->session()->forget(self::key($token));
    }

    private static function key(string $token): string
    {
        return 'share-link.'.hash('sha256', $token);
    }
}
