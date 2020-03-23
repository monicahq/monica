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
        $first = true;
        $queryString = '';
        $searchTerms = explode(' ', $searchTerm);

        foreach ($searchTerms as $searchTerm) {
            $searchTerm = DBHelper::connection()->getPdo()->quote('%'.$searchTerm.'%');

            foreach ($array as $column) {
                if ($first) {
                    $first = false;
                } else {
                    $queryString .= ' or ';
                }
                $queryString .= $column.' LIKE '.$searchTerm;
            }
        }

        return $queryString;
    }

    /**
     * Test if string is null or whitespace.
     *
     * @param  mixed $text
     * @return bool
     */
    public static function isNullOrWhitespace($text): bool
    {
        return ctype_space($text) || $text === '' || is_null($text);
    }
}
