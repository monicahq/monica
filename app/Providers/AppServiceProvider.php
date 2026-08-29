<?php

declare(strict_types=1);

namespace App\Providers;

use App\Helpers\LocaleCollation;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // How the application orders a list it is holding whole by the reader's
        // language. Everything outside `LocaleCollation` reaches it this way
        // rather than through the helper, so a call site reads as ordinary
        // collection work and says nothing about ICU.
        if (! Collection::hasMacro('sortByCollator')) {
            Collection::macro('sortByCollator', function (callable|string $callback, bool $descending = false): Collection {
                /** @var Collection<array-key, mixed> $collect */
                $collect = $this;

                return LocaleCollation::sortByCollator($collect, $callback, $descending);
            });
        }
    }
}
