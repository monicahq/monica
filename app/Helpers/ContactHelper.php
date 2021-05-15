<?php

namespace App\Helpers;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;

class ContactHelper
{
    /**
     * Get the name order that will be used when rendered the Add/Edit forms
     * about contacts.
     *
     * @param User $user
     * @param Builder $query
     * @return Builder
     */
    public static function orderContactQueryByUserPreference(User $user, $query): Builder
    {
        switch ($user->name_order) {
            case 'firstname_lastname':
                $query = $query->orderBy('first_name')
                  ->orderBy('last_name');
                break;
            case 'firstname_lastname_nickname':
                $query = $query->orderBy('first_name')
                  ->orderBy('last_name')
                  ->orderBy('nickname');
                break;
            case 'firstname_nickname_lastname':
                $query = $query->orderBy('first_name')
                  ->orderBy('nickname')
                  ->orderBy('last_name');
                break;
            case 'nickname':
                $query = $query->orderBy('nickname');
                break;
            case 'lastname_firstname':
                $query = $query->orderBy('last_name')
                  ->orderby('first_name');
                break;
            case 'lastname_firstname_nickname':
                $query = $query->orderBy('last_name')
                ->orderby('first_name')
                ->orderby('nickname');
              break;
            case 'lastname_nickname_firstname':
                $query = $query->orderBy('last_name')
                ->orderby('nickname')
                ->orderby('first_name');
              break;
        }

        return $query;
    }
}
