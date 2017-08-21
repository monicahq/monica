<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Search for needle in the columns defined by $searchable_columns.
     *
     * @param  Builder $builder query builder
     * @param  $needle
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeSearch(Builder $builder, $needle, $accountId)
    {
        if ($this->searchable_columns == null) {
            return;
        }

        // building the query. there is probably a way to make this more elegant.
        $count = count($this->searchable_columns);
        $counter = 1;
        $queryString = '';
        foreach ($this->searchable_columns as $column) {
            $queryString .= $column.' LIKE \'%'.$needle.'%\'';
            if ($counter != $count) {
                $queryString .= ' or ';
            }
            $counter++;
        }

        $builder->whereRaw('account_id = '.$accountId.' and ('.$queryString.')');
        $builder->select($this->return_from_search);

        return $builder->get();
    }
}
