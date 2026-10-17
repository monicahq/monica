<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cookie;

class PageSize
{
    /** @var list<int> */
    public const array CHOICES = [10, 20, 50, 100, 500];

    public const string ALL = 'all';

    public const string COOKIE = 'page_sizes';

    private const int COOKIE_MINUTES = 60 * 24 * 365;

    /**
     * The size to page at: the one the address names, which is then remembered for this screen in
     * a cookie, or the one remembered for it, or the screen's default.
     */
    public static function from(Request $request, int $default): int|string
    {
        $chosen = self::choice($request->query('size'));
        $screen = $request->route()?->getName();

        if ($chosen !== null && $screen !== null) {
            self::remember($request, $screen, $chosen);
        }

        if ($chosen !== null) {
            return $chosen;
        }

        $remembered = $screen === null ? null : (self::remembered($request)[$screen] ?? null);

        return self::choice(is_int($remembered) ? (string) $remembered : $remembered) ?? $default;
    }

    private static function choice(mixed $size): int|string|null
    {
        if (! is_string($size)) {
            return null;
        }

        if ($size === self::ALL) {
            return self::ALL;
        }

        if (ctype_digit($size) && in_array((int) $size, self::CHOICES, true)) {
            return (int) $size;
        }

        return null;
    }

    /** @return array<string, mixed> */
    private static function remembered(Request $request): array
    {
        $cookie = $request->cookie(self::COOKIE);

        if (! is_string($cookie)) {
            return [];
        }

        $sizes = json_decode($cookie, true);

        return is_array($sizes) ? $sizes : [];
    }

    private static function remember(Request $request, string $screen, int|string $size): void
    {
        $sizes = [...self::remembered($request), $screen => $size];

        Cookie::queue(self::COOKIE, (string) json_encode($sizes), self::COOKIE_MINUTES);
    }

    /**
     * @template TModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  Builder<TModel>|Relation<TModel, TDeclaringModel, mixed>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    public static function paginate(Builder|Relation $query, int|string $size): LengthAwarePaginator
    {
        $perPage = $size === self::ALL ? max($query->count(), 1) : (int) $size;

        $paginator = $query->paginate($perPage);

        if ($paginator->currentPage() > $paginator->lastPage()) {
            $paginator = $query->paginate($perPage, ['*'], 'page', $paginator->lastPage());
        }

        return $paginator->withQueryString();
    }
}
