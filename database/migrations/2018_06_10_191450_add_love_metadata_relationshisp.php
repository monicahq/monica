<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLoveMetadataRelationshisp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metadata_love_relationships', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('relationship_id');
            $table->boolean('is_active');
            $table->mediumText('notes')->nullable();
            $table->datetime('meet_date')->nullable();
            $table->datetime('official_date')->nullable();
            $table->datetime('breakup_date')->nullable();
            $table->mediumText('breakup_reason')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('relationship_id')->references('id')->on('relationships')->onDelete('cascade');
        });
    }
}
