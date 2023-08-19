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
        if (! Schema::hasColumn('groups', 'vcard')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->mediumText('vcard')->nullable()->after('name');
            });
        }
        if (! Schema::hasColumn('groups', 'distant_uuid')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->string('distant_uuid', 256)->nullable()->after('vcard');
            });
        }
        if (! Schema::hasColumn('groups', 'distant_etag')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->string('distant_etag', 256)->nullable()->after('distant_uuid');
            });
        }
        if (! Schema::hasColumn('groups', 'distant_uri')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->string('distant_uri', 2096)->nullable()->after('distant_etag');
            });
        }
        if (! Schema::hasColumn('groups', 'deleted_at')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->softDeletes()->after('distant_uri');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('groups', 'deleted_at')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
        if (Schema::hasColumn('groups', 'distant_uri')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('distant_uri');
            });
        }
        if (Schema::hasColumn('groups', 'distant_etag')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('distant_etag');
            });
        }
        if (Schema::hasColumn('groups', 'distant_uuid')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('distant_uuid');
            });
        }
        if (Schema::hasColumn('groups', 'vcard')) {
            Schema::table('groups', function (Blueprint $table) {
                $table->dropColumn('vcard');
            });
        }
    }
};
