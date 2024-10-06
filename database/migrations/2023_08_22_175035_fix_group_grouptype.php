<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->unsignedBigInteger('group_type_id')->nullable()->change();

            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('groups_group_type_id_foreign');
                $table->foreign('group_type_id')->references('id')->on('group_types')->nullOnDelete();
            }
        });
    }
};
