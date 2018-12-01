<?php
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


namespace App\Helpers;

use Auth;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactFieldType;

class SearchHelper
{
    /**
     * Search contacts by the given query.
     *
     * @param  string $query
     * @param  int $limitPerPage
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function searchContacts($query, $limitPerPage, $order)
    {
        $needle = $query;
        $accountId = auth()->user()->account_id;

        if (preg_match('/(.{1,})[:](.{1,})/', $needle, $matches)) {
            $search_field = $matches[1];
            $search_term = $matches[2];

            $field = ContactFieldType::where('account_id', $accountId)
                ->where('name', 'LIKE', $search_field)
                ->first();

            $field_id = is_null($field) ? 0 : $field->id;

            $results = Contact::whereHas('contactFields', function ($query) use ($accountId, $field_id, $search_term) {
                $query->where([
                    ['account_id', $accountId],
                    ['data', 'like', "$search_term%"],
                    ['contact_field_type_id', $field_id],
                ]);
            })->paginate($limitPerPage);
        } else {
            $results = Contact::search($needle, $accountId, $limitPerPage, $order, 'AND is_partial = FALSE');
        }

        return $results;
    }
}
