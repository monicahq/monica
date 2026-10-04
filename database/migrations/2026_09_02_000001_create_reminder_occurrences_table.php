<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Reminder;
use App\Models\Vault;
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
            $table->foreignIdFor(Account::class)->comment('account the deadline belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault it belongs to, copied from the rule')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Reminder::class)->comment('rule that produced it')->constrained()->cascadeOnDelete();
            $table->date('local_date')->comment('day the deadline falls on, in the zone frozen on the rule');
            $table->string('generation_key', 64)->comment('stable identity of this deadline under the rule: the schedule revision and the local date');
            $table->timestamp('scheduled_for')->comment('instant it is notified at, in UTC: the local date at the rule\'s time, never moved');
            $table->timestamp('notifications_created_at')->nullable()->comment('when delivery made its notification rows, null until it has');
            $table->timestamp('notified_at')->nullable()->comment('when the first notification for it actually went out');
            $table->string('answer', 16)->nullable()->comment('what the person said about this date, a ReminderAnswerEnum value (remembered, forgotten), null without an answer; never changed once written');
            $table->timestamp('answered_at')->nullable()->comment('when the answer was recorded');
            $table->timestamp('cancelled_at')->nullable()->comment('when it was cancelled, null while it is not');
            $table->string('cancelled_reason', 32)->nullable()->comment('why it was cancelled, a ReminderOccurrenceCancelledReasonEnum value, so nothing vanishes unexplained');
            $table->timestamps();

            $table->unique(['reminder_id', 'generation_key']);

            $table->index(['vault_id', 'local_date']);
            $table->index(['scheduled_for', 'notifications_created_at'], 'reminder_occurrences_delivery_index');
            $table->index(['vault_id', 'notified_at']);
        });
    }
};
