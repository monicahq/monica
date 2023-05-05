<?php

namespace App\Helpers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Str;

class NameHelper
{
    /**
     * Format the name of the contact according to the user preferences.
     *
     * Users can format the name however they want, using variables like
     * %first_name%, %last_name%, %middle_name%, %nickname%, %maiden_name%, and
     * so on). We need to parse this string and replace the variables with the
     * appropriate values.
     */
    public static function formatContactName(User $user, Contact $contact): string
    {
        $allCharacters = str_split($user->name_order);

        $variableFound = false;
        $variableName = '';
        $completeName = '';

        if ($contact->prefix) {
            $completeName = $contact->prefix.' ';
        }

        foreach ($allCharacters as $char) {
            if ($char === '%' && ! $variableFound) {
                // a variable has been found
                $variableFound = true;
                $variableName = $variableName.$char;
            } elseif ($char !== '%' && $variableFound) {
                $variableName = $variableName.$char;
            } elseif ($char === '%') {
                // the variable has ended
                // get rid of the first %
                $variableName = substr($variableName, 1);
                $completeName = $completeName.$contact->{$variableName};

                // reset the variable
                $variableFound = false;
                $variableName = '';
            } else {
                // this is a normal character
                $completeName = $completeName.$char;
            }
        }

        // in some cases, the user will add parenthesis to add, for instance,
        // the nickname, but if the nickname is not set, we need to remove them
        // from being displayed
        $completeName = str_replace('()', '', $completeName);
        $completeName = Str::of($completeName)->rtrim();

        if ($contact->suffix) {
            $completeName = $completeName.' '.$contact->suffix;
        }

        if (trim($completeName) === '') {
            $completeName = trans('Unknown name');
        }

        return $completeName;
    }
}
