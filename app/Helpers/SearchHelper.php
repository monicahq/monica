<?php

namespace App\Helpers;

use function Safe\preg_match;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact\ContactFieldType;

class SearchHelper
{
    /**
     * Search contacts by the given query.
     *
     * @param  string $query
     * @param  int $limitPerPage
     * @return mixed
     */
    public static function searchContacts($query, $limitPerPage, $order)
    {
        $needle = $query;
        $accountId = Auth::user()->account_id;

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
            $results = Contact::search($needle, $accountId, $limitPerPage, $order, 'AND `'.DB::connection()->getTablePrefix().'contacts`.`is_partial` = FALSE');
        }

        return $results;
    }
}
