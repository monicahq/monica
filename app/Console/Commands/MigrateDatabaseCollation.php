<?php

namespace App\Console\Commands;

use App\Helpers\DBHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\ConfirmableTrait;

class MigrateDatabaseCollation extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:collation
                            {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update database for utf8mb4 collation';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if ($this->confirmToProceed()) {
            try {
                $connection = DBHelper::connection();

                if ($connection->getDriverName() != 'mysql') {
                    return;
                }

                $databasename = $connection->getDatabaseName();

                $schemata = $connection->table('information_schema.schemata')
                    ->select('DEFAULT_CHARACTER_SET_NAME')
                    ->where('schema_name', '=', $databasename)
                    ->get();

                $schema = $schemata->first()->DEFAULT_CHARACTER_SET_NAME;

                if (config('database.use_utf8mb4') && $schema == 'utf8') {
                    $this->line('Migrate to utf8mb4 schema collation');
                    $this->toUtf8mb4($connection, $databasename);
                } elseif (! config('database.use_utf8mb4') && $schema == 'utf8mb4') {
                    $this->line('Migrate to utf8 schema collation');
                    $this->toUtf8($connection, $databasename);
                } else {
                    $this->info('Nothing to migrate, everything is ok.');
                }
            } catch (\Exception $e) {
                $this->error('                                                                      ');
                $this->error('  Check if the DB_USE_UTF8MB4 variable in .env file is correctly set  ');
                $this->error('                                                                      ');
                $this->info('');
                throw $e;
            }
        }
    }

    /**
     * Switch to utf8mb4.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $databasename
     */
    private function toUtf8mb4($connection, $databasename)
    {
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
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        DB::statement('ALTER DATABASE `'.$databasename.'` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;');
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Switch to utf8.
     *
     * @param  \Illuminate\Database\Connection  $connection
     * @param  string  $databasename
     */
    private function toUtf8($connection, $databasename)
    {
        // Tables
        $tables = $connection->table('information_schema.tables')
                            ->select('table_name')
                            ->where('table_schema', '=', $databasename)
                            ->get();

        foreach ($tables as $table) {
            DB::statement('ALTER TABLE `'.$table->table_name.'` CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;');
        }

        // Database
        $pdo = $connection->getPdo();
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        DB::statement('ALTER DATABASE `'.$databasename.'` CHARACTER SET = utf8 COLLATE = utf8_unicode_ci;');
        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    }
}
