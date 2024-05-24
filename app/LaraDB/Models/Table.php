<?php

namespace App\LaraDB\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Table
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getRouteURL(): string
    {
        return route('db.index', ['table' => $this->name]);
    }

    public function getColumnValues(int $offset = 0, int $limit = 300): Collection
    {
        $columns = Schema::getColumns($this->name);
        $rows = DB::table($this->name)
            ->limit($limit)
            ->get();

        $columnsCollection = collect();
        $rowsCollection = collect();

        // we add the column names as the first row
        foreach ($columns as $column) {
            $columnsCollection->push([
                'value' => $column['name'],
            ]);
        }
        $rowsCollection->push($columnsCollection);

        // then we add the actual data
        foreach ($rows as $row) {
            $columnsCollection = collect();
            foreach ($columns as $column) {
                $columnsCollection->push([
                    'value' => $row->{$column['name']},
                ]);
            }
            $rowsCollection->push($columnsCollection);
        }

        return $rowsCollection;
    }
}
