<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveDeletedAtFromNotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropColumn(
                'deleted_at', 'title', 'activity_date', 'remind_for_next_call_or_email', 'author_id'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->dateTime('activity_date')->nullable();
            $table->string('remind_for_next_call_or_email')->default('false');
            $table->integer('author_id');
            $table->softDeletes();
        });
    }
}
