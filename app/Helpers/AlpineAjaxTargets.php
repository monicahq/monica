<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;

class AlpineAjaxTargets
{
    /**
     * The ids of the elements an Alpine Ajax request will replace, read from the X-Alpine-Target
     * header. Empty for an ordinary request, which wants the whole page.
     *
     * @return list<string>
     */
    public static function from(Request $request): array
    {
        if (! $request->hasHeader('X-Alpine-Request')) {
            return [];
        }

        return array_values(array_filter(explode(' ', (string) $request->header('X-Alpine-Target', ''))));
    }
}
