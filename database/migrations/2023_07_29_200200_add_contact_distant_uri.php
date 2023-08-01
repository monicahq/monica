<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('contacts', 'distant_uuid')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->string('distant_uuid', 256)->nullable()->after('vcard');
            });
        }
        if (! Schema::hasColumn('contacts', 'distant_uri')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->string('distant_uri', 2096)->nullable()->after('distant_etag');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('contacts', 'distant_uri')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('distant_uri');
            });
        }
        if (Schema::hasColumn('contacts', 'distant_uuid')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropColumn('distant_uuid');
            });
        }
    }
};
