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

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class FixJsonColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = DB::connection();

        if ($connection->getDriverName() != 'mysql') {
            return;
        }

        $databasename = $connection->getDatabaseName();

        $columns = DB::select(
            'select table_name, column_name from information_schema.columns where table_schema = ? and data_type = ? '.
            ' and table_name in (?, ?, ?)',
            [$databasename, 'json', 'default_life_event_types', 'life_event_types', 'life_events']
        );
        $tablePrefix = DB::connection()->getTablePrefix();

        foreach ($columns as $column) {
            DB::statement('ALTER TABLE `'.$databasename.'`.`'.$tablePrefix.$column->table_name.'` MODIFY `'.$column->column_name.'` text;');
        }
    }
}
