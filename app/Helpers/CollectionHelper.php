<?php

namespace App\Helpers;

class CollectionHelper
{
    /**
     * Sort the collection using the given callback.
     *
     * @param  callable|string  $callback
     * @param  int  $options
     * @param  bool  $descending
     * @return static
     */
    public static function sortByCollator($collect, $callback)
    {
        $results = [];

        $callback = static::valueRetriever($callback);

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($collect->all() as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $collator = \Collator::create(\App::getLocale());
        $collator->asort($results, \Collator::SORT_STRING);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $collect->get($key);
        }

        return new \Illuminate\Support\Collection($results);
        
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  string  $value
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
}
