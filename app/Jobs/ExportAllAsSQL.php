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
     * @return void
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
