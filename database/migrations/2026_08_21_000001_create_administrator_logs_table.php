<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrator_logs', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(User::class)->nullable()->comment('administrator who took the action, null when the console acted')->constrained()->nullOnDelete();
            $table->string('user_email')->comment('address the administrator had at the time, so the entry outlives them');
            $table->string('action')->comment('what was done, an AdministratorActionEnum value');
            $table->json('parameters')->nullable()->comment('what the sentence needs to read, such as a name or a number of bytes');
            $table->foreignIdFor(Account::class)->nullable()->comment('account the action was taken on, so the record filters by account')->constrained()->nullOnDelete();
            $table->string('subject_type')->nullable()->comment('class of what was acted on');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('id of that object, with no foreign key since it may be gone');
            $table->string('ip_address', 45)->nullable()->comment('address the administrator acted from');
            $table->timestamps();

            $table->index('created_at');
            $table->index('account_id');
            $table->index(['subject_type', 'subject_id'], 'administrator_logs_subject_index');
        });
    }
};
