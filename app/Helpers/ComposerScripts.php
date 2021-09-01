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
     *
     * @codeCoverageIgnore
     */
    public static function preInstall($event)
    {
        try {
            if (file_exists('vendor')) {
                \Illuminate\Foundation\ComposerScripts::postInstall($event);
            }
            static::clear();
        } catch (\Throwable $e) {
            // catch all
        }
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
        try {
            if (file_exists('vendor')) {
                \Illuminate\Foundation\ComposerScripts::postUpdate($event);
            }
            static::clear();
        } catch (\Throwable $e) {
            // catch all
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected static function clear()
    {
        if (file_exists(self::CONFIG)) {
            \unlink(self::CONFIG); /** @phpstan-ignore-line */
        }
    }
}
