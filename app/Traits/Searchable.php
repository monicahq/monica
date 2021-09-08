<?php

namespace App\Traits;

use App\Helpers\DBHelper;
use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Search for needle in the columns defined by $searchable_columns.
     *
     * @param  Builder  $builder  query builder
     * @param  string  $needle
     * @param  int  $accountId
     * @param  string  $orderByColumn
     * @param  string  $orderByDirection
     * @param  string  $sortOrder
     * @return Builder|null
     */
    public function scopeSearch(Builder $builder, string $needle, int $accountId, string $orderByColumn, string $orderByDirection = 'asc', string $sortOrder = null): ?Builder
    {
        if ($this->searchable_columns == null) {
            return null;
        }

        $searchableColumns = array_map(function ($column) {
            return DBHelper::getTable($this->getTable()).".`$column`";
        }, $this->searchable_columns);

        $queryString = $this->buildQuery($searchableColumns, $needle);

        $builder->whereRaw(DBHelper::getTable($this->getTable()).".`account_id` = $accountId")
            ->whereRaw("( $queryString )")
            ->orderBy($orderByColumn, $orderByDirection);

        if ($sortOrder) {
            $builder->sortedBy($sortOrder);
        }

        $builder->select(array_map(function ($column) {
            return "{$this->getTable()}.$column";
        }, $this->return_from_search));

        return $builder;
    }

    /**
     * Build a query based on the array that contains column names.
     *
     * @param  array  $array
     * @param  string  $searchTerm
     * @return string
     */
    private function buildQuery(array $array, string $searchTerm): string
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
                    $queryString .= ' OR ';
                }
                $queryString .= $column.' LIKE '.$searchTerm;
            }
        }

        return $queryString;
    }
}
