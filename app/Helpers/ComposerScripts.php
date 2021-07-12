<?php

namespace App\Helpers;

use function Safe\fopen;
use function Safe\fclose;
use function Safe\fwrite;
use function Safe\substr;

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

    /**
     * @codeCoverageIgnore
     */
    public static function compile()
    {
        $vals = [];

        $gitdir = __DIR__.'/../../.git';
        if (is_dir($gitdir)) {
            $vals['.version'] = trim(trim(shell_exec("git --git-dir $gitdir describe --abbrev=0 --tags"), 'v'));
            $vals['.commit'] = trim(shell_exec("git --git-dir $gitdir log --pretty=%H -n1 HEAD"));

            $release = '';
            try {
                $release = trim(shell_exec("git --git-dir $gitdir describe --abbrev=0 --tags --exact-match HEAD"));
            } catch (\Exception $e) {
                // No error
            }
            if ($release === '') {
                $release = trim(shell_exec("git --git-dir $gitdir log --pretty=%h -n1 HEAD"));
            }
            $vals['.release'] = $release;
        } elseif (env('SOURCE_VERSION') === '') {
            $vals['.commit'] = env('SOURCE_VERSION');
            $vals['.release'] = substr(env('SOURCE_VERSION'), 0, 8);
        }

        foreach ($vals as $key => $val) {
            $file = fopen(__DIR__.'/../../config/'.$key, 'w');
            fwrite($file, $val);
            fclose($file);
        }
    }
}
