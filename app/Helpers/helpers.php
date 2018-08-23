<?php

use App\Helpers\LocaleHelper;

if (! function_exists('direction')) {
    /**
     * Get the direction: left to right/right to left.
     *
     * @return string
     * @see LocaleHelper::getDirection()
     */
    function direction()
    {
        return LocaleHelper::getDirection();
    }
}
