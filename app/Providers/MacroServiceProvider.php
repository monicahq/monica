<?php

namespace App\Providers;

use App\Helpers\CollectionHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! Collection::hasMacro('sortByCollator')) {
            Collection::macro('sortByCollator', function ($callback, $options = \Collator::SORT_STRING, $descending = false) {
                /** @var Collection */
                $collect = $this;

                return CollectionHelper::sortByCollator($collect, $callback, $options, $descending);
            });
        }
    }
}
