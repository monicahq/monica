<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRelationshipTableIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add some indexes
        Schema::table('relationship_type_groups', function (Blueprint $table) {
            $table->index(['account_id', 'name']);
        });
        Schema::table('default_relationship_types', function (Blueprint $table) {
            $table->index(['migrated']);
        });
    }
}
