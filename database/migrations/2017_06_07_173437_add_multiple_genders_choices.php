<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultipleGendersChoices extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $enumValues = ['male', 'female', 'none'];
        $this->migrateTables($enumValues);
    }

    /**
     * @param $enumValues
     */
    protected function migrateTables($enumValues)
    {
        $this->addNewGenderEnumsForTable('contacts', $enumValues);
        $this->addNewGenderEnumsForTable('significant_others', $enumValues);
        $this->addNewGenderEnumsForTable('kids', $enumValues);
    }

    private function addNewGenderEnumsForTable($tableName, $enumValues)
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->renameColumn('gender', 'tmp_gender');
        });

        Schema::table($tableName, function (Blueprint $table) use ($enumValues) {
            $table->enum('gender', $enumValues)->default('none');
        });

        $all = DB::table($tableName)->get();
        foreach ($all as $item) {
            DB::table($tableName)->where('id', $item['id'])->update(['gender' => $item['tmp_gender']]);
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('tmp_gender');
        });
    }

    public function down()
    {
        $enumValues = ['male', 'female'];
        $this->migrateTables($enumValues);
    }

}
