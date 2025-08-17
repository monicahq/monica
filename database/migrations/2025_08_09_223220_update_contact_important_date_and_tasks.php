<?php

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasUuids;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_important_dates', function (Blueprint $table) {
            $table->uuid('uuid')->after('contact_id')->nullable();

            $table->mediumText('vcalendar')->after('uuid')->nullable();
            $table->string('distant_uuid', 256)->after('vcalendar')->nullable();
            $table->string('distant_etag', 256)->after('distant_uuid')->nullable();
            $table->string('distant_uri', 2096)->after('distant_etag')->nullable();

            $table->softDeletes();
            $table->index('uuid');
        });

        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->uuid('uuid')->after('author_id')->nullable();

            $table->mediumText('vcalendar')->after('uuid')->nullable();
            $table->string('distant_uuid', 256)->after('vcalendar')->nullable();
            $table->string('distant_etag', 256)->after('distant_uuid')->nullable();
            $table->string('distant_uri', 2096)->after('distant_etag')->nullable();

            $table->softDeletes();
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
            $table->dropColumn('vcalendar');
            $table->dropColumn('distant_uuid');
            $table->dropColumn('distant_etag');
            $table->dropColumn('distant_uri');
            $table->dropSoftDeletes();
        });

        Schema::table('contact_important_dates', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
            $table->dropColumn('vcalendar');
            $table->dropColumn('distant_uuid');
            $table->dropColumn('distant_etag');
            $table->dropColumn('distant_uri');
            $table->dropSoftDeletes();
        });
    }
};
