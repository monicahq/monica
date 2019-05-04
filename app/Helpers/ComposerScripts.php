<?php

namespace App\Helpers;

use function Safe\unlink;

class ComposerScripts
{
    const CONFIG = 'bootstrap/cache/config.php';

    /**
     * Handle the pre-install Composer event.
     *
     * @param  mixed  $event
     * @return void
     *
     * @codeCoverageIgnore
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
     *
     * @codeCoverageIgnore
     */
    public static function preUpdate($event)
    {
        if (file_exists('vendor')) {
            \Illuminate\Foundation\ComposerScripts::postUpdate($event);
        }
        static::clear();
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function clear()
    {
        if (file_exists(self::CONFIG)) {
            unlink(self::CONFIG);
        }
    }
}
