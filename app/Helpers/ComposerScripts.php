<?php

namespace App\Helpers;

use Composer\Script\Event;

class ComposerScripts
{
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
        if (file_exists('bootstrap/cache/config.php')) {
            exec('php artisan config:clear');
        }
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
        if (file_exists('bootstrap/cache/config.php')) {
            exec('php artisan config:clear');
        }
    }
}
