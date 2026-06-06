<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tag;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a polymorphic pivot table so that tags can be attached
     * to any model in the future — not just contacts.
     * This table is used exclusively by the API layer and future features
     * (e.g. tagging activities, notes, etc.).
     *
     * Schema:
     *   tag_id    — FK to tags.id
     *   taggable_id — FK to the tagged model's PK  (UUID string for contacts)
     *   taggable_type — the model class name string (e.g. "App\Models\Contact")
     */
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tag_id')
                ->constrained(Tag::class)
                ->cascadeOnDelete();

            // Polymorphic columns — taggable_id is a string to support UUID PKs
            $table->string('taggable_id');
            $table->string('taggable_type');

            $table->timestamps();

            // Prevent duplicate tag assignments
            $table->unique(['tag_id', 'taggable_id', 'taggable_type'], 'taggables_unique');

            // "All tags for a taggable entity"
            $table->index(['taggable_id', 'taggable_type'], 'taggables_taggable_index');

            $table->index('tag_id', 'taggables_tag_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
