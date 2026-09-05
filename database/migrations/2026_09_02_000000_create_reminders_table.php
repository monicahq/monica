<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier of the rule, used wherever it is addressed from outside');
            $table->unsignedBigInteger('account_id')->comment('account the rule belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault it lives in');
            $table->string('remindable_type')->comment('class of the record it is about, a contact today');
            $table->unsignedBigInteger('remindable_id')->comment('id of that record');
            $table->unsignedBigInteger('field_id')->comment('date field it reads');
            $table->unsignedBigInteger('field_value_id')->nullable()->comment('exact value it reads, which is what caps it at one live rule per value, null once that value has been cleared');
            $table->string('name')->comment('what the reminder says when it fires');
            $table->string('source', 32)->comment('where the need comes from, a ReminderSourceEnum value');
            $table->string('cadence', 32)->comment('how often it fires, a ReminderCadenceEnum value, once included');
            $table->unsignedTinyInteger('frequency_interval')->default(1)->comment('how many of those between firings, 1 unless somebody set a rhythm of their own');
            $table->json('moments')->comment('when to be told, a list of direction, amount and unit, at least one');
            $table->time('time_of_day')->comment('local time it fires at');
            $table->string('timezone', 64)->comment('zone frozen when the rule was made, which a later preference never moves');
            $table->unsignedInteger('schedule_revision')->default(1)->comment('bumped every time the schedule changes, so a regenerated deadline never collides with one cancelled under the old schedule');
            $table->boolean('is_active')->default(true)->comment('whether the rule still produces anything');
            $table->string('inactive_reason', 32)->nullable()->comment('why it stopped, a ReminderInactiveReasonEnum value, null while it is active');
            $table->timestamp('deactivated_at')->nullable()->comment('when it stopped');
            $table->unsignedBigInteger('created_by_user_id')->nullable()->comment('user who created it');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
            $table->index('vault_id');
            $table->index('field_value_id');
            $table->index(['remindable_type', 'remindable_id'], 'reminders_remindable_index');

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('field_id')->references('id')->on('fields')->cascadeOnDelete();
            $table->foreign('field_value_id')->references('id')->on('field_values')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
