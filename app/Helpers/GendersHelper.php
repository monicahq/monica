<?php

namespace App\Helpers;

class GendersHelper
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
}
