<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tag;
use App\Models\Contact;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds:
     *  - tag_category  (nullable string) to tags table — groups tags into
     *    categories like "Personal", "Work", "Networking"
     *  - color         (nullable string) to tags table — hex color code
     *  - indexes on contact_tag pivot for the two main query patterns:
     *      1. All contacts with a given tag  → index on tag_id
     *      2. All tags for a contact         → index on contact_id
     */
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            // Optional grouping (e.g. "Personal", "Work", "Networking")
            $table->string('tag_category')->nullable()->after('slug');

            // Hex color code — the existing bg_color/text_color are Tailwind
            // class strings; this column is for a plain hex value used by the
            // API (e.g. "#FF5733"). Nullable so existing rows are unaffected.
            $table->string('color', 7)->nullable()->after('tag_category');

            // Index for "find all contacts with a given tag"
            $table->index('vault_id', 'tags_vault_id_index');
        });

        Schema::create('contact_tag', function (Blueprint $table) {
            $table->foreignId('tag_id')
                ->constrained(Tag::class)
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained(Contact::class)
                ->cascadeOnDelete();

            // "All contacts with a given tag"
            $table->index('tag_id', 'contact_tag_tag_id_index');

            // "All tags for a contact"
            $table->index('contact_id', 'contact_tag_contact_id_index');

            // Composite unique index for counting: how many contacts per tag
            $table->unique(['tag_id', 'contact_id'], 'contact_tag_tag_contact_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('contact_tag');
    }
};
