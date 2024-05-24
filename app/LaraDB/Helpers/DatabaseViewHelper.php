<?php

namespace App\LaraDB\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseViewHelper
{
    public static function getDatabaseInformation(): array
    {
        $connection = config('database.default');

        return [
            'database_name' => DB::connection()->getDatabaseName(),
            'type' => config("database.connections.{$connection}.driver"),
        ];
    }

    public static function getTablesInformation(array $tables): Collection
    {
        $tablesCollection = collect();
        foreach ($tables as $table) {
            $tablesCollection->push([
                'name' => $table['name'],
                'route' => route('db.index', ['table' => $table['name']]),
            ]);
        }

        return $tablesCollection;
    }
}
