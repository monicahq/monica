<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DBHelper
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public static function getTables()
    {
        return DB::table('information_schema.tables')
            ->select('table_name')
            ->where('table_schema', '=', DB::connection()->getDatabaseName())
            ->get();
    }
}
