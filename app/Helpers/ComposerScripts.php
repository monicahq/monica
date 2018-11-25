<?php

namespace App\Helpers;

class ComposerScripts
{
    const CONFIG = 'bootstrap/cache/config.php';

    /**
     * Handle the pre-install Composer event.
     *
     * @param  mixed  $event
     * @return void
     */
    public static function preInstall($event)
    {
        if (file_exists('vendor')) {
            \Illuminate\Foundation\ComposerScripts::postInstall($event);
        }
        static::clear();
    }

    /**
     * Handle the pre-update Composer event.
     *
     * @param  mixed  $event
     * @return void
     */
    public static function preUpdate($event)
    {
        if (file_exists('vendor')) {
            \Illuminate\Foundation\ComposerScripts::postUpdate($event);
        }
        static::clear();
    }

    protected static function clear()
    {
        if (file_exists(self::CONFIG)) {
            unlink(self::CONFIG);
        }
    }
}
