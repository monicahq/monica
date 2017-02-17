<?php

namespace App\Helpers;

class RandomHelper
{
    /**
     * Generate a random string of characters.
     *
     * @param  int length The length of the desired string
     * @return string A random string of characters
     */
    public static function generateString($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0987654321'.time();
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = $length; $i > 0; $i--) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
