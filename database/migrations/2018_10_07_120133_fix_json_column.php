<?php

use App\Helpers\DBHelper;
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
        $connection = DBHelper::connection();

        if ($connection->getDriverName() != 'mysql') {
            return;
        }

        $databasename = $connection->getDatabaseName();

        $columns = DB::select(
            'select table_name, column_name from information_schema.columns where table_schema = ? and data_type = ? '.
            ' and table_name in (?, ?, ?)',
            [$databasename, 'json', 'default_life_event_types', 'life_event_types', 'life_events']
        );

        foreach ($columns as $column) {
            DB::statement('ALTER TABLE `'.$databasename.'`.'.DBHelper::getTable($column->table_name).' MODIFY `'.$column->column_name.'` text;');
        }
    }
}
