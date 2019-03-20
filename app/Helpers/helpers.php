<?php

use App\Helpers\LocaleHelper;

// @codeCoverageIgnoreStart
if (! function_exists('htmldir')) {
// @codeCoverageIgnoreEnd
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
