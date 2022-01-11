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

        if (! Collection::hasMacro('groupByItemsProperty')) {
            Collection::macro('groupByItemsProperty', function ($property) {
                /** @var Collection */
                $collect = $this;

                return CollectionHelper::groupByItemsProperty($collect, $property);
            });
        }

        if (! Collection::hasMacro('mapUuid')) {
            Collection::macro('mapUuid', function () {
                /** @var Collection */
                $collect = $this;

                return $collect->map(function ($item) {
                    return $item->uuid;
                })->toArray();
            });
        }
    }
}
