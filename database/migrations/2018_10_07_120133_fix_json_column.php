<?php

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

        $columns = $connection->table('information_schema.columns')
                            ->select('table_name', 'column_name')
                            ->where([
                                ['table_schema', '=', $databasename],
                                ['data_type', '=', 'json'],
                            ])
                            ->whereIn('table_name', [
                                'default_life_event_types',
                                'life_event_types',
                                'life_events',
                            ])
                            ->get();

        foreach ($columns as $column) {
            DB::statement('ALTER TABLE `'.$databasename.'`.`'.$column->table_name.'` MODIFY `'.$column->column_name.'` text;');
        }
    }
}
