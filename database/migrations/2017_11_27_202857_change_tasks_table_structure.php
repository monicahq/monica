<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTasksTableStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('completed')->default(0)->after('description');
            $table->boolean('archived')->default(0)->after('completed');
            $table->date('archived_at')->nullable()->after('completed_at');
        });

        DB::table('tasks')
            ->where('status', 'completed')
            ->update(['completed' => 1]);

        DB::table('tasks')
            ->where('status', 'archived')
            ->update(['archived' => 1]);

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
