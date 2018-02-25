<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Build a query based on the array that contains column names.
     *
     * @param  array  $array
     * @return string
     */
    public static function buildQuery(array $array, string $searchTerm)
    {
        $count = count($array);
        $counter = 1;
        $queryString = '';

        foreach ($array as $column) {
            $queryString .= $column.' LIKE \'%'.$searchTerm.'%\'';
            if ($counter != $count) {
                $queryString .= ' or ';
            }
            $counter++;
        }

        return $queryString;
    }
}
