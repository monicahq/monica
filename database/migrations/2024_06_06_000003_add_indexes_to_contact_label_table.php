<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds indexes to contact_label table for efficient querying of:
     * - Finding all contacts with a given tag
     * - Finding all tags for a contact
     * - Counting tag usage
     */
    public function up(): void
    {
        Schema::table('contact_label', function (Blueprint $table) {
            // Ensure composite primary key and add indexes if they don't exist
            $table->index('label_id');
            $table->index('contact_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_label', function (Blueprint $table) {
            $table->dropIndex(['label_id']);
            $table->dropIndex(['contact_id']);
        });
    }
};
