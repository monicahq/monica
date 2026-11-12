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
        Schema::create('support_requests', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->unsignedInteger('number')->unique()->comment('random number people quote, shown as MON-number, never sequential');
            $table->foreignIdFor(Account::class)->comment('account the request belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->comment('user who opened it')->constrained()->nullOnDelete();
            $table->string('category')->comment('what the request is about, a SupportCategoryEnum value');
            $table->string('subject')->comment('short summary written by the user');
            $table->string('status')->comment('where the request stands, a SupportRequestStatusEnum value');
            $table->string('application_version')->comment('version of the application when the request was opened');
            $table->string('locale')->comment('language of the user when the request was opened');
            $table->timestamp('last_activity_at')->comment('when either side last wrote in the thread');
            $table->timestamp('solved_at')->nullable()->comment('when it was last moved to solved');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['account_id', 'last_activity_at']);
        });
    }
};
