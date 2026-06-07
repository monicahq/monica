<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE import_jobs MODIFY COLUMN file_type ENUM('vcard', 'csv') NOT NULL DEFAULT 'vcard'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE import_jobs MODIFY COLUMN file_type ENUM('vcard') NOT NULL DEFAULT 'vcard'");
    }
};
