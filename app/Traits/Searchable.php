<?php

namespace App\Traits;

use App\Helpers\StringHelper;
use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Search for needle in the columns defined by $searchable_columns.
     *
     * @param  Builder $builder query builder
     * @param  string $needle
     * @param  int  $accountId
     * @param  int $limitPerPage
     * @param  string $sortOrder
     * @param  string $whereCondition
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|null
     */
    public function scopeSearch(Builder $builder, $needle, $accountId, $limitPerPage, $sortOrder, $whereCondition = null)
    {
        if ($this->searchable_columns == null) {
            return;
        }

        $queryString = StringHelper::buildQuery($this->searchable_columns, $needle);

        $builder->whereRaw('account_id = '.$accountId.' and ('.$queryString.') '.$whereCondition);
        $builder->orderByRaw($sortOrder);
        $builder->select($this->return_from_search);

        return $builder->paginate($limitPerPage);
    }
}
