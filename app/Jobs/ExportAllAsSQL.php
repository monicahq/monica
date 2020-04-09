<?php

namespace App\Jobs;

use App\Helpers\DBHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportAllAsSQL
{
    use Dispatchable;

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        $sql = '# ************************************************************
# Monica data export.
# Export date: '.now().'
# ************************************************************

'.PHP_EOL;

        $tables = DBHelper::getTables();

        // Looping over the tables
        foreach ($tables as $table) {
            $tableName = $table->table_name;

            $tableData = DB::table($tableName)->get();

            // Looping over the rows
            foreach ($tableData as $data) {
                $newSQLLine = 'INSERT INTO '.$tableName.' (';
                $tableValues = [];

                // Looping over the column names
                $tableColumnNames = [];
                foreach ($data as $columnName => $value) {
                    array_push($tableColumnNames, $columnName);
                }

                $newSQLLine .= implode(',', $tableColumnNames).') VALUES (';

                // Looping over the values
                foreach ($data as $columnName => $value) {
                    if (is_null($value)) {
                        $value = 'NULL';
                    } elseif (! is_numeric($value)) {
                        $value = "'".addslashes($value)."'";
                    }

                    array_push($tableValues, $value);
                }

                $newSQLLine .= implode(',', $tableValues).');'.PHP_EOL;
                $sql .= $newSQLLine;
            }
        }
        $filename = 'export-all-'.time().'.sql';
        Storage::disk('local')->put($filename, $sql);

        return $filename;
    }
}
