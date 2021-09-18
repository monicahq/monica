<?php

namespace App\Helpers;

use function Safe\preg_match;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Auth;
use App\Models\Contact\ContactFieldType;
use Illuminate\Database\Eloquent\Builder;

class SearchHelper
{
    /**
     * Search contacts by the given query.
     *
     * @param  string  $needle
     * @param  string  $orderByColumn
     * @param  string  $orderByDirection
     * @param  string|null  $addressBookName
     * @return Builder
     */
    public static function searchContacts(string $needle, string $orderByColumn, string $orderByDirection = 'asc', string $addressBookName = null): Builder
    {
        $accountId = Auth::user()->account_id;

        // match against `field: string` queries
        if (preg_match('/(.{1,})[:](.{1,})/', $needle, $matches)) {
            $search_field = $matches[1];
            $search_term = $matches[2];

            $field = ContactFieldType::where('account_id', $accountId)
                ->where('name', 'LIKE', $search_field)
                ->first();

            $field_id = is_null($field) ? 0 : $field->id;

            /** @var Builder */
            $builder = Contact::whereHas('contactFields', function ($query) use ($accountId, $field_id, $search_term) {
                $query->where([
                    ['account_id', $accountId],
                    ['data', 'like', "$search_term%"],
                    ['contact_field_type_id', $field_id],
                ]);
            });

            return $builder->addressBook($accountId, $addressBookName)
                     ->orderBy($orderByColumn, $orderByDirection);
        }

        return Contact::search($needle, $accountId, $orderByColumn, $orderByDirection)
            ->addressBook($accountId, $addressBookName);
    }
}
