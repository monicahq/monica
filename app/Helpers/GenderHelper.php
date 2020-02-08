<?php

namespace App\Helpers;

use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;

class GenderHelper
{
    /**
     * Return a collection of genders.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getGendersInput()
    {
        $genders = auth()->user()->account->genders->map(function ($gender) {
            return [
                'id' => $gender->id,
                'name' => $gender->name,
            ];
        });
        $genders = CollectionHelper::sortByCollator($genders, 'name');
        $genders->prepend(['id' => '', 'name' => trans('app.gender_no_gender')]);

        return $genders;
    }

    /**
     * Replaces a specific gender of all the contacts in the account with another
     * gender.
     *
     * @param Account $account
     * @param Gender $genderToDelete
     * @param Gender $genderToReplaceWith
     * @return bool
     */
    public static function replace(Account $account, Gender $genderToDelete, Gender $genderToReplaceWith): bool
    {
        Contact::where('account_id', $account->id)
            ->where('gender_id', $genderToDelete->id)
            ->update(['gender_id' => $genderToReplaceWith->id]);

        return true;
    }
}
