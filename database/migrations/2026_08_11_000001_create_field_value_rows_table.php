<?php

declare(strict_types=1);

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
        Schema::create('field_value_rows', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the row belongs to');
            $table->string('valuable_type')->comment('class of the record the row belongs to, a contact, an entity or an activity');
            $table->unsignedBigInteger('valuable_id')->comment('id of that record');
            $table->unsignedBigInteger('field_id')->comment('repeater field this is a repetition of');
            $table->unsignedInteger('position')->comment('order the repetition appears in, which the user can reorder');
            $table->timestamps();

            $table->index(['valuable_type', 'valuable_id', 'field_id', 'position'], 'field_value_rows_record_field_index');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('field_id')->references('id')->on('fields')->cascadeOnDelete();
        });
    }
};
