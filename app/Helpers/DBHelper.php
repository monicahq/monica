<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DBHelper
{
    public static function getTables()
    {
        return DB::table('information_schema.tables')
            ->select('table_name')
            ->where('table_schema', '=', static::getDefaultConnectionConfiguration('database'))
            ->get();
    }

    public static function getDefaultConnectionConfiguration($key = null, $default = null)
    {
        $defaultConnection = config('database.default');
        $configuration = config('database.connections.'.$defaultConnection, []);

        return $key ? array_get($configuration, $key, $default) : $configuration;
    }
}
