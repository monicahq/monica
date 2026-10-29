<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This polymorphic taggables table allows tags/labels to be attached to
     * multiple model types (contacts, activities, etc.) in the future.
     * Currently used for contacts, but designed for extensibility.
     */
    public function up(): void
    {
        if (!Schema::hasTable('taggables')) {
            Schema::create('taggables', function (Blueprint $table) {
                $table->id();
                $table->foreignId('label_id')
                    ->constrained('labels')
                    ->cascadeOnDelete()
                    ->comment('Reference to label/tag');
                
                $table->morphs('taggable');
                
                $table->timestamps();
                
                // Composite unique index for efficient queries
                $table->unique(['taggable_id', 'taggable_type', 'label_id']);
                // morphs() already creates index on (taggable_type, taggable_id)
                $table->index('label_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
