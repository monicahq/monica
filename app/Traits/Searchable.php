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
    public function scopeSearch(Builder $builder, $needle)
    {
        if ($this->searchable_columns == null) {
            return null;
        }
        foreach ($this->searchable_columns as $column) {
            $builder->orWhere($column, 'LIKE', '%' . $needle . '%');
        }

        $builder->select($this->return_from_search);

        return $builder->get();
    }
}