<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_events', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->unsignedBigInteger('account_id')->comment('account the life event belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault it lives in');
            $table->unsignedBigInteger('life_event_type_id')->comment('nature of the life event');
            $table->unsignedBigInteger('structure_version_id')->comment('exact field structure used when it was created');
            $table->date('happened_on')->comment('the day it happened, or the first day when it spans several');
            $table->date('ended_on')->nullable()->comment('the last day, null when it happened on a single day');
            $table->string('date_precision', 16)->default('day')->comment('how precisely the date is known, a DatePrecisionEnum value');
            $table->time('happened_at_time')->nullable()->comment('time of day, null when it does not matter or is not known');
            $table->unsignedInteger('duration_in_minutes')->nullable()->comment('how long it lasted, null when unknown');
            $table->string('timezone', 64)->nullable()->comment('time zone the time was entered in, so it does not shift when the user moves');
            $table->string('title')->nullable()->comment('short label, null when the type and the participants say enough');
            $table->text('narrative')->nullable()->comment('the free account of what happened');
            $table->string('location')->nullable()->comment('where it happened, in words');
            $table->decimal('latitude', 10, 7)->nullable()->comment('latitude, null when no place was pinned');
            $table->decimal('longitude', 10, 7)->nullable()->comment('longitude, null the same way');
            $table->boolean('is_shareable')->default(false)->comment('whether a share link may include it at all');
            $table->boolean('narrative_is_shareable')->default(false)->comment('whether the free account may be included');
            $table->string('created_from', 16)->default('contact')->comment('which entry path produced it, a LifeEventSourceEnum value');
            $table->unsignedBigInteger('created_by_user_id')->nullable()->comment('user who entered it, null when an import did');
            $table->unsignedInteger('participant_count')->default(0)->comment('running count of participants');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vault_id', 'happened_on']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('life_event_type_id')->references('id')->on('life_event_types')->cascadeOnDelete();
            $table->foreign('structure_version_id')->references('id')->on('structure_versions')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
