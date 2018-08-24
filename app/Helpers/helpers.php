<?php

use App\Helpers\LocaleHelper;

if (! function_exists('htmldir')) {
    /**
     * Get the direction: left to right/right to left.
     *
     * @return string
     * @see LocaleHelper::getDirection()
     */
    function htmldir()
    {
        return LocaleHelper::getDirection();
    }
}
