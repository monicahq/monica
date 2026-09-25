<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->json('errors')->nullable()->after('error_log');
        });
    }

    public function down()
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropColumn('errors');
        });
    }
};
