<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
