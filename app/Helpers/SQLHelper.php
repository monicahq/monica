<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SQLHelper
{
    /**
     * Extract the year.
     */
    public static function year(string $dateColumnName): string
    {
        /** @var \Illuminate\Database\Connection */
        $connection = DB::connection();

        switch ($connection->getDriverName()) {
            case 'sqlite':
                $query = 'strftime("%Y", '.$dateColumnName.')';
                break;

            case 'pgsql':
                $query = 'date_part(\'year\', '.$dateColumnName.')';
                break;

            default:
                $query = 'YEAR('.$dateColumnName.')';
                break;
        }

        return $query;
    }
}
