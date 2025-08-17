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
        if (! Schema::hasIndex('contact_feed_items', ['feedable_type', 'feedable_id'])) {
            Schema::table('contact_feed_items', function (Blueprint $table) {
                $table->index(['feedable_type', 'feedable_id']);
            });
        }
    }
};
