<?php

if (! function_exists('direction')) {
    /**
     * Get the direction: left to right/right to left.
     *
     * @return string
     * @see \App\Helpers\LocaleHelper::getDirection()
     */
    function direction()
    {
        return \App\Helpers\LocaleHelper::getDirection();
    }
}
