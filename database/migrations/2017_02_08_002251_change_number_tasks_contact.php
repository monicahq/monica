<?php

use App\Traits\MigrationHelper;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNumberTasksContact extends Migration
{
    use MigrationHelper;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'number_of_tasks'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $this->default($table->integer('number_of_tasks_in_progress'), 0)->after('number_of_gifts_offered');
            $this->default($table->integer('number_of_tasks_completed'), 0)->after('number_of_tasks_in_progress');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'number_of_tasks_completed', 'number_of_gifts_offered'
            );
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('number_of_tasks');
        });
    }
}
