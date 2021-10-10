<?php

use App\Helpers\DBHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddMultipleGendersChoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driverName = DBHelper::connection()->getDriverName();
        switch ($driverName) {
            case 'mysql':
                DB::statement('ALTER TABLE '.DBHelper::getTable('contacts')." CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
                DB::statement('ALTER TABLE '.DBHelper::getTable('significant_others')." CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
                DB::statement('ALTER TABLE '.DBHelper::getTable('kids')." CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
                break;
            case 'pgsql':
                $this->alterEnum(DBHelper::getTable('contacts'), 'gender', ['male', 'female', 'none']);
                $this->alterEnum(DBHelper::getTable('significant_others'), 'gender', ['male', 'female', 'none']);
                $this->alterEnum(DBHelper::getTable('kids'), 'gender', ['male', 'female', 'none']);
                break;
            default:
                throw new \Exception("Driver {$driverName} not supported.");
                break;
        }
    }

    /**
     * Alter an enum field constraints. Source: https://stackoverflow.com/a/36198549.
     *
     * @param $table
     * @param $field
     * @param  array  $options
     */
    protected function alterEnum($table, $field, array $options)
    {
        $check = "${table}_${field}_check";
        $enumList = [];
        foreach ($options as $option) {
            $enumList[] = sprintf("'%s'::CHARACTER VARYING", $option);
        }
        $enumString = implode(', ', $enumList);
        DB::transaction(function () use ($table, $field, $check, $enumString) {
            DB::statement(sprintf('ALTER TABLE %s DROP CONSTRAINT %s;', $table, $check));
            DB::statement(sprintf('ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s::TEXT = ANY (ARRAY[%s]::TEXT[]))', $table, $check, $field, $enumString));
        });
    }
}
