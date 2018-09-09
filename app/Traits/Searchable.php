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
     * @param  $needle
     * @param  int  $accountId
     * @param  int $limitPerPage
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
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
