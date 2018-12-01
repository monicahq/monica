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

class AddMultipleGendersChoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driverName = DB::connection()->getDriverName();
        $tablePrefix = DB::connection()->getTablePrefix();
        switch ($driverName) {
            case 'mysql':
                DB::statement('ALTER TABLE '.$tablePrefix."contacts CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
                DB::statement('ALTER TABLE '.$tablePrefix."significant_others CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
                DB::statement('ALTER TABLE '.$tablePrefix."kids CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
                break;
            case 'pgsql':
                $this->alterEnum($tablePrefix.'contacts', 'gender', ['male', 'female', 'none']);
                $this->alterEnum($tablePrefix.'significant_others', 'gender', ['male', 'female', 'none']);
                $this->alterEnum($tablePrefix.'kids', 'gender', ['male', 'female', 'none']);
                break;
            default:
                throw new \Exception("Driver {$driverName} not supported.");
                break;
        }
    }

    /**
     * Alter an enum field constraints. Source: https://stackoverflow.com/a/36198549.
     * @param $table
     * @param $field
     * @param array $options
     */
    protected function alterEnum($table, $field, array $options)
    {
        $check = "${table}_${field}_check";
        $enumList = [];
        foreach ($options as $option) {
            $enumList[] = sprintf("'%s'::CHARACTER VARYING", $option);
        }
        $enumString = implode(', ', $enumList);
        DB::transaction(function () use ($table, $field, $check, $options, $enumString) {
            DB::statement(sprintf('ALTER TABLE %s DROP CONSTRAINT %s;', $table, $check));
            DB::statement(sprintf('ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s::TEXT = ANY (ARRAY[%s]::TEXT[]))', $table, $check, $field, $enumString));
        });
    }
}
