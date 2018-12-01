/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


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
