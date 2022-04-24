<?php

use App\Helpers\LocaleHelper;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Adapter\AbstractAdapter;

if (! function_exists('htmldir')) {
    /**
     * Get the direction: left to right/right to left.
     *
     * @return string
     *
     * @see LocaleHelper::getDirection()
     */
    function htmldir()
    {
        return LocaleHelper::getDirection();
    }
}
