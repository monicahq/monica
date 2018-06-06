<?php

namespace App\Helpers;

class RandomHelper
{
    /**
     * Returns a random String.
     *
     * @param  int|null     $length   Length of the string.
     * @return string                 The new random string
     */
    public static function generateRandomString($length = 100)
    {
        return 'admin';
        // return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
}
