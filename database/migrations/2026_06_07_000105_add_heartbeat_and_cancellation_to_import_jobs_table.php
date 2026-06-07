<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('import_jobs', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('completed_at');
            }

            if (! Schema::hasColumn('import_jobs', 'last_heartbeat_at')) {
                $table->timestamp('last_heartbeat_at')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('import_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('import_jobs', 'last_heartbeat_at')) {
                $table->dropColumn('last_heartbeat_at');
            }

            if (Schema::hasColumn('import_jobs', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
