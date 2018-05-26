<?php

namespace App\Helpers;

use PDO;
use Illuminate\Support\Facades\DB;

class DBHelper
{
    /**
     * Get the version of DB engine.
     *
     * @return string
     */
    public static function version()
    {
        try {
            return DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Test if db version if greater than $version param.
     *
     * @param string
     * @return bool
     */
    public static function testVersion($version)
    {
        return version_compare(static::version(), $version) >= 0;
    }
}
