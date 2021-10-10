<?php

namespace App\Helpers;

use App\Models\User\User;

class FormHelper
{
    /**
     * Get the name order that will be used when rendered the Add/Edit forms
     * about contacts.
     *
     * @param  User  $user
     * @return string
     */
    public static function getNameOrderForForms(User $user): string
    {
        $nameOrder = 'firstname';

        switch ($user->name_order) {
            case 'lastname_firstname':
            case 'lastname_firstname_nickname':
            case 'lastname_nickname_firstname':
            case 'nickname_lastname_firstname':
                $nameOrder = 'lastname';
                break;
        }

        return $nameOrder;
    }
}
