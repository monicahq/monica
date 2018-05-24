<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ConvertMysqlEncoding extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = DB::connection();

        if ($connection->getDriverName() == 'mysql') {
            $databasename = $connection->getDatabaseName();

            // Tables
            $tables = $connection->table('information_schema.tables')
                ->select('table_name')
                ->where('table_schema', '=', $databasename)
                ->get();

            foreach ($tables as $table) {
                DB::statement('ALTER TABLE `'.$table->table_name.'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');
            }

            // Database
            $pdo = $connection->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            DB::statement('ALTER DATABASE `'.$databasename.'` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;');
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
    }
}
