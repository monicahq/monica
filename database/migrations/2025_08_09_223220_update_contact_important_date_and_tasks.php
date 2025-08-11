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
            $table->softDeletes();
            $table->index('uuid');
        });
        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->uuid('uuid')->after('author_id')->nullable();
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
            $table->dropSoftDeletes();
        });
        Schema::table('contact_important_dates', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropColumn('uuid');
            $table->dropSoftDeletes();
        });
    }
};
