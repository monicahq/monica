<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;
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
            $table->foreignIdFor(Account::class)->comment('account the rule belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault it lives in')->constrained()->cascadeOnDelete();
            $table->string('remindable_type')->comment('class of the record it is about, a contact today');
            $table->unsignedBigInteger('remindable_id')->comment('id of that record');
            $table->foreignIdFor(Field::class)->nullable()->comment('date field it reads, null for a manual rule')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(FieldValue::class)->nullable()->comment('exact value it reads, which is what caps it at one live rule per value, null once that value has been cleared')->constrained()->nullOnDelete();
            $table->string('name')->comment('what the reminder says when it fires');
            $table->string('source', 32)->comment('where the need comes from, a ReminderSourceEnum value: manual or field_value');
            $table->date('starts_on')->nullable()->comment('first date of a manual rule, null for a field rule, which reads its date from the value');
            $table->string('cadence', 32)->comment('how often it fires, a ReminderCadenceEnum value, once included');
            $table->unsignedTinyInteger('frequency_interval')->default(1)->comment('how many of those between firings, 1 unless somebody set a rhythm of their own');
            $table->time('time_of_day')->comment('local time it fires at');
            $table->string('timezone', 64)->comment('zone frozen when the rule was made, which a later preference never moves');
            $table->unsignedInteger('schedule_revision')->default(1)->comment('bumped every time the schedule changes, so a regenerated deadline never collides with one cancelled under the old schedule');
            $table->boolean('is_active')->default(true)->comment('whether the rule still produces anything');
            $table->string('inactive_reason', 32)->nullable()->comment('why it stopped, a ReminderInactiveReasonEnum value (value_cleared, record_archived, record_deleted, field_removed), null while it is active');
            $table->timestamp('deactivated_at')->nullable()->comment('when it stopped');
            $table->foreignIdFor(User::class, 'created_by_user_id')->nullable()->comment('user who created it')->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'is_active']);
            $table->index('vault_id');
            $table->index('field_value_id');
            $table->index(['remindable_type', 'remindable_id'], 'reminders_remindable_index');
        });
    }
};
