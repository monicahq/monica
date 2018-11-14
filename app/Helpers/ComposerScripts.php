<?php

namespace App\Helpers;

use Composer\Script\Event;

class ComposerScripts
{
    const CONFIG = 'bootstrap/cache/config.php';

    /**
     * Handle the pre-install Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function preInstall(Event $event)
    {
        if (file_exists('vendor')) {
            \Illuminate\Foundation\ComposerScripts::postInstall($event);
        }
        static::clear();
    }

    /**
     * Handle the pre-update Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function preUpdate(Event $event)
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
