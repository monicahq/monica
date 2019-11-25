<?php

use App\Helpers\LocaleHelper;
use Illuminate\Support\Facades\Storage;

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

if (! function_exists('disk_adapter')) {
    /**
     * Get the adapter for a disk.
     *
     * @param  string|null  $disk
     * @return \League\Flysystem\Adapter\AbstractAdapter
     */
    function disk_adapter($disk = null)
    {
        $driver = Storage::disk($disk)->getDriver();
        if ($driver instanceof \League\Flysystem\Filesystem) {
            $adapter = $driver->getAdapter();
            if ($adapter instanceof \League\Flysystem\Adapter\AbstractAdapter) {
                return $adapter;
            }
        }
    }
}
