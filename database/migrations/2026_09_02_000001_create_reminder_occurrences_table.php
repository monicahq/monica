<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_occurrences', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier of the deadline, used wherever it is addressed from outside');
            $table->unsignedBigInteger('account_id')->comment('account the deadline belongs to');
            $table->unsignedBigInteger('vault_id')->comment('vault it belongs to, copied from the rule');
            $table->unsignedBigInteger('reminder_id')->comment('rule that produced it');
            $table->date('source_local_date')->comment('local date of the cadence cycle, before its moment was applied');
            $table->string('moment_direction', 16)->comment('direction copied from the moment that produced it, a ReminderMomentDirectionEnum value');
            $table->unsignedSmallInteger('moment_amount')->nullable()->comment('amount copied from that moment, null when it falls on the day itself');
            $table->string('moment_unit', 16)->nullable()->comment('unit copied from that moment, a ReminderMomentUnitEnum value, null when it falls on the day itself');
            $table->string('generation_key', 64)->comment('stable identity of this generated deadline, unique under the rule, built from the schedule revision, the cycle date and the normalised moment');
            $table->timestamp('scheduled_for')->comment('instant it is due, in UTC, moved when the deadline is postponed');
            $table->timestamp('original_scheduled_for')->comment('instant it was first generated for, kept for ever');
            $table->date('local_date')->comment('local day the current instant falls on, so a day can be queried without converting');
            $table->string('status', 32)->comment('what became of it, a ReminderOccurrenceStatusEnum value');
            $table->timestamp('postponed_to')->nullable()->comment('instant it was moved to, null when it was not');
            $table->unsignedInteger('postponed_count')->default(0)->comment('how many times it was moved, so a deadline nobody acts on is visible');
            $table->timestamp('notified_at')->nullable()->comment('when the first notification for it actually went out');
            $table->timestamp('dismissed_at')->nullable()->comment('when somebody stopped this one deadline from interrupting them again');
            $table->timestamp('cancelled_at')->nullable()->comment('when it was cancelled');
            $table->string('status_before_cancellation', 32)->nullable()->comment('actionable state restored when a reversible cancellation is reversed');
            $table->string('cancelled_reason', 32)->nullable()->comment('why it was cancelled, a ReminderOccurrenceCancelledReasonEnum value, so nothing vanishes unexplained');
            $table->timestamps();

            $table->unique(['reminder_id', 'generation_key']);

            $table->index(['status', 'scheduled_for']);
            $table->index(['reminder_id', 'status', 'scheduled_for'], 'reminder_occurrences_rule_state_index');
            $table->index(['account_id', 'local_date']);

            $table->index(['vault_id', 'notified_at']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('reminder_id')->references('id')->on('reminders')->cascadeOnDelete();
        });
    }
};
