<?php

namespace App\Traits;

use App\Helpers\DBHelper;
use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait Searchable
{
    /**
     * Search for needle in the columns defined by $searchable_columns.
     *
     * @param  Builder $builder query builder
     * @param  string $needle
     * @param  int  $accountId
     * @param  string $orderBy
     * @return Builder|null
     */
    public function scopeSearch(Builder $builder, $needle, $accountId, $orderBy): ?Builder
    {
        if ($this->searchable_columns == null) {
            return null;
        }

        $searchableColumns = array_map(function ($column) {
            return DBHelper::getTable($this->getTable()).".`$column`";
        }, $this->searchable_columns);

        $queryString = StringHelper::buildQuery($searchableColumns, $needle);

        $builder->whereRaw(DBHelper::getTable($this->getTable()).".`account_id` = $accountId AND ($queryString)");
        $builder->orderByRaw($orderBy);

        $builder->select(array_map(function ($column) {
            return "{$this->getTable()}.$column";
        }, $this->return_from_search));

        return $builder;
    }
}
