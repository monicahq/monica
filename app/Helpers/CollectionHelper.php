<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CollectionHelper
{
    /**
     * Sort the collection using the given callback.
     *
     * @param  \Illuminate\Support\Collection  $collect
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return Collection
     */
    public static function sortByCollator($collect, $callback, $options = \Collator::SORT_STRING, $descending = false)
    {
        $results = [];

        $callback = static::valueRetriever($callback);

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($collect->all() as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        // Using Collator to sort the array, with locale-sensitive sort ordering support.
        static::getCollator()->asort($results, $options);
        if ($descending) {
            $results = array_reverse($results);
        }

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $collect->get($key);
        }

        return new Collection($results);
    }

    /**
     * Get a Collator object for the locale or current locale.
     *
     * @param  string  $locale
     * @return \Collator
     */
    public static function getCollator($locale = null)
    {
        static $collators = [];

        if (! $locale) {
            $locale = app()->getLocale();
        }
        if (! Arr::has($collators, $locale)) {
            $collator = new \Collator($locale);

            if (LocaleHelper::getLang($locale) == 'fr') {
                $collator->setAttribute(\Collator::FRENCH_COLLATION, \Collator::ON);
            }

            $collators[$locale] = $collator;

            return $collator;
        }

        return $collators[$locale];
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  string|callable  $value
     * @return callable
     */
    private static function valueRetriever($value)
    {
        if (! is_string($value) && is_callable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Group collection based on a specific property from its items.
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @param  string  $property
     * @return mixed
     */
    public static function groupByItemsProperty($collection, $property)
    {
        return $collection->mapToGroups(function ($item) use ($property) {
            return [data_get($item, $property) => $item];
        });
    }
}
