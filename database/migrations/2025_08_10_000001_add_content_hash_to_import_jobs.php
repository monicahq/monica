<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('file_size');
            $table->index(['vault_id', 'content_hash', 'status'], 'import_jobs_dedup_index');
        });
    }

    public function down()
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            $table->dropIndex('import_jobs_dedup_index');
            $table->dropColumn('content_hash');
        });
    }
};
