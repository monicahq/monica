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
        Schema::table('labels', function (Blueprint $table) {
            $table->string('category')->nullable()->after('slug')->comment('Tag category: Personal, Work, Networking, etc.');
            $table->string('color')->default('#6B7280')->after('bg_color')->comment('Tag color for UI rendering');
            
            // Add indexes for query optimization
            $table->index('vault_id');
            $table->index('slug');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labels', function (Blueprint $table) {
            $table->dropIndex(['vault_id']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'color']);
        });
    }
};
